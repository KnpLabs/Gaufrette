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
    protected $key;
    protected $filesystem;

    /**
     * Content variable is lazy. It will not be read from filesystem until it's requested first time.
     *
     * @var mixed content
     */
    protected $content = null;

    /**
     * @var array metadata in associative array. Only for adapters that support metadata
     */
    protected $metadata = null;

    /**
     * Human readable filename (usually the end of the key).
     *
     * @var string name
     */
    protected $name = null;

    /**
     * File size in bytes.
     *
     * @var int size
     */
    protected $size = 0;

    /**
     * File date modified.
     *
     * @var int mtime
     */
    protected $mtime = null;

    /**
     * @param string     $key
     * @param FilesystemInterface $filesystem
     */
    public function __construct($key, FilesystemInterface $filesystem)
    {
        $this->key = $key;
        $this->name = $key;
        $this->filesystem = $filesystem;
    }

    /**
     * Returns the key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the content.
     *
     * @throws FileNotFound
     *
     * @param array $metadata optional metadata which should be set when read
     *
     * @return string
     */
    public function getContent($metadata = [])
    {
        if (isset($this->content)) {
            return $this->content;
        }
        $this->setMetadata($metadata);

        return $this->content = $this->filesystem->read($this->key);
    }

    /**
     * @return string name of the file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int size of the file
     */
    public function getSize()
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
     *
     * @return int
     */
    public function getMtime()
    {
        return $this->mtime = $this->filesystem->mtime($this->key);
    }

    /**
     * @param int $size size of the file
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Sets the content.
     *
     * @param string $content
     * @param array  $metadata optional metadata which should be send when write
     *
     * @return int The number of bytes that were written into the file, or
     *             FALSE on failure
     */
    public function setContent($content, $metadata = [])
    {
        $this->content = $content;
        $this->setMetadata($metadata);

        return $this->size = $this->filesystem->write($this->key, $this->content, true);
    }

    /**
     * @param string $name name of the file
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Indicates whether the file exists in the filesystem.
     *
     * @return bool
     */
    public function exists()
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
    public function delete($metadata = [])
    {
        $this->setMetadata($metadata);

        return $this->filesystem->delete($this->key);
    }

    /**
     * Creates a new file stream instance of the file.
     *
     * @return Stream
     */
    public function createStream()
    {
        return $this->filesystem->createStream($this->key);
    }

    /**
     * Rename the file and move it to its new location.
     *
     * @param string $newKey
     */
    public function rename($newKey)
    {
        $this->filesystem->rename($this->key, $newKey);

        $this->key = $newKey;
    }

    /**
     * Sets the metadata array to be stored in adapters that can support it.
     *
     * @param array $metadata
     *
     * @return bool
     */
    protected function setMetadata(array $metadata)
    {
        if ($metadata && $this->supportsMetadata()) {
            $this->filesystem->getAdapter()->setMetadata($this->key, $metadata);

            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function supportsMetadata()
    {
        return $this->filesystem->getAdapter() instanceof MetadataSupporter;
    }
}
