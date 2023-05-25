<?php

namespace Gaufrette\Stream;

use Gaufrette\Stream;
use Gaufrette\Filesystem;
use Gaufrette\StreamMode;
use Gaufrette\Util;

class InMemoryBuffer implements Stream
{
    private Filesystem $filesystem;
    private string $key;
    private ?StreamMode $mode = null;
    private string $content;
    private int $numBytes;
    private int $position;
    private bool $synchronized = false;

    /**
     * @param Filesystem $filesystem The filesystem managing the file to stream
     * @param string     $key        The file key
     */
    public function __construct(Filesystem $filesystem, string $key)
    {
        $this->filesystem = $filesystem;
        $this->key = $key;
    }

    public function open(StreamMode $mode): bool
    {
        $this->mode = $mode;

        $exists = $this->filesystem->has($this->key);

        if (($exists && !$mode->allowsExistingFileOpening())
            || (!$exists && !$mode->allowsNewFileOpening())) {
            return false;
        }

        if ($mode->impliesExistingContentDeletion()) {
            $this->content = $this->writeContent('');
        } elseif (!$exists && $mode->allowsNewFileOpening()) {
            $this->content = $this->writeContent('');
        } else {
            $this->content = $this->filesystem->read($this->key);
        }

        $this->numBytes = Util\Size::fromContent($this->content);
        $this->position = $mode->impliesPositioningCursorAtTheEnd() ? $this->numBytes : 0;

        $this->synchronized = true;

        return true;
    }

    public function read(int $count): string|false
    {
        if (false === $this->mode->allowsRead()) {
            throw new \LogicException('The stream does not allow read.');
        }

        $chunk = substr($this->content, $this->position, $count);
        $this->position += Util\Size::fromContent($chunk);

        return $chunk;
    }

    public function write(string $data): int
    {
        if (false === $this->mode->allowsWrite()) {
            throw new \LogicException('The stream does not allow write.');
        }

        $numWrittenBytes = Util\Size::fromContent($data);

        $newPosition = $this->position + $numWrittenBytes;
        $newNumBytes = $newPosition > $this->numBytes ? $newPosition : $this->numBytes;

        if ($this->eof()) {
            $this->numBytes += $numWrittenBytes;
            if ($this->hasNewContentAtFurtherPosition()) {
                $data = str_pad($data, $this->position + strlen($data), ' ', STR_PAD_LEFT);
            }
            $this->content .= $data;
        } else {
            $before = substr($this->content, 0, $this->position);
            $after = $newNumBytes > $newPosition ? substr($this->content, $newPosition) : '';
            $this->content = $before . $data . $after;
        }

        $this->position = $newPosition;
        $this->numBytes = $newNumBytes;
        $this->synchronized = false;

        return $numWrittenBytes;
    }

    public function close(): void
    {
        if (!$this->synchronized) {
            $this->flush();
        }
    }

    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;

                break;
            case SEEK_CUR:
                $this->position += $offset;

                break;
            case SEEK_END:
                $this->position = $this->numBytes + $offset;

                break;
            default:
                return false;
        }

        return true;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function flush(): bool
    {
        if ($this->synchronized) {
            return true;
        }

        try {
            $this->writeContent($this->content);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function eof(): bool
    {
        return $this->position >= $this->numBytes;
    }

    /**
     * @return array<string, mixed>|false
     */
    public function stat(): array|false
    {
        if ($this->filesystem->has($this->key)) {
            $isDirectory = $this->filesystem->isDirectory($this->key);
            $time = $this->filesystem->mtime($this->key);

            $stats = [
                'dev' => 1,
                'ino' => 0,
                'mode' => $isDirectory ? 16893 : 33204,
                'nlink' => 1,
                'uid' => 0,
                'gid' => 0,
                'rdev' => 0,
                'size' => $isDirectory ? 0 : Util\Size::fromContent($this->content),
                'atime' => $time,
                'mtime' => $time,
                'ctime' => $time,
                'blksize' => -1,
                'blocks' => -1,
            ];

            return array_merge(array_values($stats), $stats);
        }

        return false;
    }

    public function cast(int $castAs): bool
    {
        return false;
    }

    public function unlink(): bool
    {
        if ($this->mode && $this->mode->impliesExistingContentDeletion()) {
            return $this->filesystem->delete($this->key);
        }

        return false;
    }

    protected function hasNewContentAtFurtherPosition(): bool
    {
        return $this->position > 0 && !$this->content;
    }

    /**
     * @param string $content   Empty string by default
     * @param bool   $overwrite Overwrite by default
     */
    protected function writeContent(string $content = '', bool $overwrite = true): string
    {
        $this->filesystem->write($this->key, $content, $overwrite);

        return $content;
    }
}
