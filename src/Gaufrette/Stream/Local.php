<?php

namespace Gaufrette\Stream;

use Gaufrette\Stream;
use Gaufrette\StreamMode;

/**
 * Local stream.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Local implements Stream
{
    private string $path;
    private $mode;
    private $fileHandle;
    private int $mkdirMode;

    /**
     * @param string $path
     * @param int    $mkdirMode
     */
    public function __construct(string $path, int $mkdirMode = 0755)
    {
        $this->path = $path;
        $this->mkdirMode = $mkdirMode;
    }

    public function open(StreamMode $mode): bool
    {
        $baseDirPath = \Gaufrette\Util\Path::dirname($this->path);
        if ($mode->allowsWrite() && !is_dir($baseDirPath)) {
            @mkdir($baseDirPath, $this->mkdirMode, true);
        }

        try {
            $fileHandle = @fopen($this->path, $mode->getMode());
        } catch (\Exception $e) {
            $fileHandle = false;
        }

        if (false === $fileHandle) {
            throw new \RuntimeException(sprintf('File "%s" cannot be opened', $this->path));
        }

        $this->mode = $mode;
        $this->fileHandle = $fileHandle;

        return true;
    }

    public function read(int $count): string|false
    {
        if (!$this->fileHandle) {
            return false;
        }

        if (false === $this->mode->allowsRead()) {
            throw new \LogicException('The stream does not allow read.');
        }

        return fread($this->fileHandle, $count);
    }

    public function write(string $data): int
    {
        if (!$this->fileHandle) {
            return 0;
        }

        if (false === $this->mode->allowsWrite()) {
            throw new \LogicException('The stream does not allow write.');
        }

        return fwrite($this->fileHandle, $data)?: 0;
    }

    public function close(): void
    {
        if (!$this->fileHandle) {
            return;
        }

        $closed = fclose($this->fileHandle);

        if ($closed) {
            $this->mode = null;
            $this->fileHandle = null;
        }
    }

    public function flush(): bool
    {
        if ($this->fileHandle) {
            return fflush($this->fileHandle);
        }

        return false;
    }

    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        if ($this->fileHandle) {
            return 0 === fseek($this->fileHandle, $offset, $whence);
        }

        return false;
    }

    public function tell(): int
    {
        if ($this->fileHandle) {
            return ftell($this->fileHandle);
        }

        return false;
    }

    public function eof(): bool
    {
        if ($this->fileHandle) {
            return feof($this->fileHandle);
        }

        return true;
    }

    /**
     * @return array<string, mixed>|false
     */
    public function stat(): array|false
    {
        if ($this->fileHandle) {
            return fstat($this->fileHandle);
        } elseif (!is_resource($this->fileHandle) && is_dir($this->path)) {
            return stat($this->path);
        }

        return false;
    }

    public function cast(int $castAs)
    {
        if ($this->fileHandle) {
            return $this->fileHandle;
        }

        return false;
    }

    public function unlink(): bool
    {
        if ($this->mode && $this->mode->impliesExistingContentDeletion()) {
            return @unlink($this->path);
        }

        return false;
    }
}
