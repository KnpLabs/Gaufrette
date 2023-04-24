<?php

namespace Gaufrette;

use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Exception\FileNotFound;

/**
 * Points to a file in a filesystem.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class File
{
    protected string $key;
    protected FilesystemInterface $filesystem;

    /**
     * Content variable is lazy. It will not be read from filesystem until it's requested first time.
     *
     * @var mixed content
     */
    protected $content = null;

    /**
     * Associative array. Only for adapters that support metadata
     * @var array<string, mixed>
     */
    protected ?array $metadata = null;

    /**
     * Human readable filename (usually the end of the key).
     */
    protected string $name;

    /**
     * File size in bytes.
     */
    protected int $size = 0;

    /**
     * File date modified.
     */
    protected ?int $mtime = null;

    /**
     * @param string     $key
     * @param FilesystemInterface $filesystem
     */
    public function __construct(string $key, FilesystemInterface $filesystem)
    {
        $this->key = $key;
        $this->name = $key;
        $this->filesystem = $filesystem;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @throws FileNotFound
     *
     * @param array<string, mixed> $metadata optional metadata which should be set when read
     */
    public function getContent(array $metadata = []): string
    {
        if (isset($this->content)) {
            return $this->content;
        }

        $this->setMetadata($metadata);

        return $this->content = $this->filesystem->read($this->key);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        if ($this->size) {
            return $this->size;
        }

        try {
            return $this->size = $this->filesystem->size($this->getKey());
        } catch (FileNotFound $exception) {
        }

        return 0;
    }

    /**
     * Returns the file modified time.
     */
    public function getMtime(): int
    {
        return $this->mtime = $this->filesystem->mtime($this->key);
    }

    /**
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * @param array<string, mixed> $metadata optional metadata which should be send when write
     *
     * @return int|false The number of bytes that were written into the file, or
     *             FALSE on failure
     */
    public function setContent(string $content, array $metadata = []): int|bool
    {
        $this->content = $content;
        $this->setMetadata($metadata);

        return $this->size = $this->filesystem->write($this->key, $this->content, true);
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Indicates whether the file exists in the filesystem.
     */
    public function exists(): bool
    {
        return $this->filesystem->has($this->key);
    }

    /**
     * Deletes the file from the filesystem.
     *
     * @throws FileNotFound
     * @throws \RuntimeException when cannot delete file
     *
     * @param array<string, mixed> $metadata optional metadata which should be send when write
     *
     * @return true on success
     */
    public function delete(array $metadata = []): bool
    {
        $this->setMetadata($metadata);

        return $this->filesystem->delete($this->key);
    }

    /**
     * Creates a new file stream instance of the file.
     */
    public function createStream(): Stream
    {
        return $this->filesystem->createStream($this->key);
    }

    /**
     * Rename the file and move it to its new location.
     */
    public function rename(string $newKey): void
    {
        $this->filesystem->rename($this->key, $newKey);

        $this->key = $newKey;
    }

    /**
     * Sets the metadata array to be stored in adapters that can support it.
     *
     * @param array<string, mixed> $metadata
     */
    protected function setMetadata(array $metadata): bool
    {
        $adapter = $this->filesystem->getAdapter();

        if ($metadata && $adapter instanceof MetadataSupporter) {
            $adapter->setMetadata($this->key, $metadata);

            return true;
        }

        return false;
    }
}
