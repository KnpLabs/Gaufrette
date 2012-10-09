<?php

namespace Gaufrette;

use Gaudrette\Adapter\MetadataSupporter;

/**
 * Points to a file in a filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class File
{
    protected $key;
    protected $filesystem;

    /**
     * Content variable is lazy. It will not be read from filesystem until it's requested first time
     * @var content
     */
    protected $content = null;

    /**
     * @var array metadata in associative array. Only for adapters that support metadata
     */
    protected $metadata = null;

    /**
     * Human readable filename (usually the end of the key)
     * @var string name
     */
    protected $name = null;

    /**
     * Moment of the initial creation
     * @var DateTime created
     */
    protected $created = null;

    /**
     * File size in bytes
     * @var int size
     */
    protected $size = null;

    /**
     * Constructor
     *
     * @param string     $key
     * @param Filesystem $filesystem
     */
    public function __construct($key, Filesystem $filesystem)
    {
        $this->key = $key;
        $this->filesystem = $filesystem;
    }

    /**
     * Returns the key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the content
     *
     * @throws Gaufrette\Exception\FileNotFound
     *
     * @return string
     */
    public function getContent()
    {
        if (isset($this->content)) {
            return $this->content;
        }

        return $this->content = $this->filesystem->read($this->key);
    }

    /**
     * Gets the metadata array if the adapter can support it
     *
     * @return array          $metadata or false
     */
    public function getMetadata()
    {
        if ($this->metadata) {
            return $this->metadata;
        }

        if ($this->supportsMetadata()) {
            return $this->metadata = $this->filesystem->getAdapter()->getMetadata($this->key);
        }

        return false;
    }

    /**
     * @return string name of the file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return DateTime created
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return int size of the file
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Sets the content
     *
     * @param string $content
     *
     * @return integer The number of bytes that were written into the file, or
     *                 FALSE on failure
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this->filesystem->write($this->key, $this->content, true);
    }

    /**
     * Sets the metadata array to be stored in adapters that can support it
     *
     * @param  array          $metadata
     */
    public function setMetadata(array $metadata)
    {
        if ($this->supportsMetadata()) {
            $this->filesystem->getAdapter()->setMetadata($this->key, $metadata);
            $this->metadata = $metadata;

            return true;
        }

        return false;
    }

    /**
     * @param string name of the file
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param \DateTime created
     */
    public function setCreated(\DateTime $created)
    {
        $this->created = $created;
    }

    /**
     * @param int size of the file
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * Indicates whether the file exists in the filesystem
     *
     * @return boolean
     */
    public function exists()
    {
        return $this->filesystem->has($this->key);
    }

    /**
     * Deletes the file from the filesystem
     *
     * @throws Gaufrette\Exception\FileNotFound
     * @throws \RuntimeException when cannot delete file
     * @return boolean TRUE on success
     */
    public function delete()
    {
        return $this->filesystem->delete($this->key);
    }

    /**
     * Creates a new file stream instance of the file
     *
     * @return FileStream
     */
    public function createStream()
    {
        return $this->filesystem->createStream($this->key);
    }

    /**
     * @return boolean
     */
    private function supportsMetadata()
    {
        return $this->filesystem->getAdapter() instanceof Adapter\MetadataSupporter;
    }
}
