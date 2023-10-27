<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Util;

/**
 * In memory adapter.
 *
 * Stores some files in memory for test purposes
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class InMemory implements Adapter, MimeTypeProvider
{
    protected array $files = [];

    /**
     * @param array<string, mixed> $files An array of files
     */
    public function __construct(array $files = [])
    {
        $this->setFiles($files);
    }

    /**
     * Defines the files.
     *
     * @param array<string, mixed> $files An array of files
     */
    public function setFiles(array $files): void
    {
        $this->files = [];
        foreach ($files as $key => $file) {
            if (!is_array($file)) {
                $file = ['content' => $file];
            }

            $file = array_merge([
                'content' => null,
                'mtime' => null,
            ], $file);

            $this->setFile($key, $file['content'], $file['mtime']);
        }
    }

    /**
     * Defines a file.
     *
     * @param string $key     The key
     * @param string $content The content
     * @param int    $mtime   The last modified time (automatically set to now if NULL)
     */
    public function setFile(string $key, string $content = null, int $mtime = null): void
    {
        if (null === $mtime) {
            $mtime = time();
        }

        $this->files[$key] = [
            'content' => (string) $content,
            'mtime' => (integer) $mtime,
        ];
    }

    public function read(string $key): string|bool
    {
        return $this->files[$key]['content'];
    }

    public function rename(string $sourceKey, mixed $targetKey): bool
    {
        $content = $this->read($sourceKey);
        $this->delete($sourceKey);

        return (boolean) $this->write($targetKey, $content);
    }

    /**
     * @param ?array<string, mixed> $metadata
     */
    public function write(string $key, mixed $content, array $metadata = null): int|bool
    {
        $this->files[$key]['content'] = $content;
        $this->files[$key]['mtime'] = time();

        return Util\Size::fromContent($content);
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->files);
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->files);
    }

    public function mtime(string $key): int|bool
    {
        return $this->files[$key]['mtime'] ?? false;
    }

    public function delete(string $key): bool
    {
        unset($this->files[$key]);
        clearstatcache();

        return true;
    }

    public function isDirectory(string $path): bool
    {
        return false;
    }

    public function mimeType(string $key): string
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        return $fileInfo->buffer($this->files[$key]['content']);
    }
}
