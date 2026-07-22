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
    /**
     * Content variable is lazy. It will not be read from filesystem until it's requested first time.
     */
    protected mixed $content = null;

    /**
     * Metadata in associative array. Only for adapters that support metadata
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
     * @param FilesystemInterface&Filesystem $filesystem
     */
    public function __construct(private string $key, private FilesystemInterface $filesystem)
    {
        $this->name = $key;
    }

    /**
     * Returns the key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Returns the content.
     *
     * @throws FileNotFound
     *
     * @param array $metadata optional metadata which should be set when read
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

    /**
     */
    public function getSize(): int
    {
        if ($this->size) {
            return $this->size;
        }

        try {
            return $this->size = $this->filesystem->size($this->getKey());
        } catch (FileNotFound $exception) {
            return 0;
        }
    }

    /**
     * @return int|false Returns the file modified time.
     */
    public function getMtime(): int|bool
    {
        return $this->mtime = $this->filesystem->mtime($this->key);
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * @param array $metadata optional metadata which should be send when write
     *
     * @return int|bool The number of bytes that were written into the file, or
     *             FALSE on failure
     */
    public function setContent(string $content, array $metadata = []): int|bool
    {
        $this->content = $content;
        $this->setMetadata($metadata);

        return $this->size = $this->filesystem->write($this->key, $this->content, true);
    }

    /**
     */
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
     * @param array $metadata optional metadata which should be send when write
     *
     * @return bool TRUE on success
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
     */
    protected function setMetadata(array $metadata): bool
    {
        if ([] === $metadata) {
            return false;
        }

        $adapter = $this->filesystem->getAdapter();

        if (false === $adapter instanceof MetadataSupporter) {
            return false;
        }

        $adapter->setMetadata($this->key, $metadata);

        return true;
    }
}
