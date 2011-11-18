<?php

namespace Gaufrette;

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
     * @param  string     $key
     * @param  Filesystem $filesystem An optional filesystem
     */
    public function __construct($key, Filesystem $filesystem = null)
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
     * Returns the filesystem
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * Returns the content
     *
     * @return string
     */
    public function getContent()
    {
        //If content has already been read for this file, just return it immediately
        if (isset($this->content)) {
            return $this->content;
        }
        if (null === $this->filesystem) {
            throw new \LogicException('The filesystem is not defined.');
        }
        if (!$this->exists()) {
            throw new \LogicException('The file does not exists in the filesystem.');
        }
        $this->content = $this->filesystem->read($this->key);

        return $this->content;
    }

    /**
     * Gets the metadata array if the adapter can support it
     *
     * @return array $metadata or null
     * @throws LogicException if metadata is not supported
     */
    public function getMetadata()
    {
        if ($this->filesystem->supportsMetadata()) {
            return $this->metadata;
        }
        throw new \LogicException("This filesystem adapter does not support metadata");
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
     * Sets the filesystem
     *
     * @param  Filesystem $filesystem
     */
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Sets the content
     *
     * @param  string $content
     *
     * @return integer The number of bytes that were written into the file, or
     *                 FALSE on failure
     */
    public function setContent($content)
    {
        if (null === $this->filesystem) {
            throw new \LogicException('The filesystem is not defined.');
        }
        $this->content = $content;

        //To maintain consistency between this object and filesystem, write immediately when content is being set.
        return $this->filesystem->write($this->key, $this->content, true);
    }

    /**
     * Sets the metadata array to be stored in adapters that can support it
     *
     * @param array $metadata
     * @throws LogicException if metadata is not supported
     */
    public function setMetadata(array $metadata)
    {
        if ($this->filesystem->supportsMetadata()) {
            $this->metadata = $metadata;
        } else {
            throw new \LogicException("This filesystem adapter does not support metadata");
        }
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
        if (null === $this->filesystem) {
            return false;
        }

        return $this->filesystem->has($this->key);
    }

    /**
     * Deletes the file from the filesystem
     *
     * @return  boolean TRUE on success, or FALSE on failure
     */
    public function delete()
    {
        if (!$this->exists()) {
            throw new \LogicException('The file could not be deleted as it does not exist.');
        }

        return $this->filesystem->delete($this->key);
    }

    /**
     * Creates a new file stream instance of the file
     *
     * @return  FileStream
     */
    public function createFileStream()
    {
        if (null === $this->filesystem) {
            throw new \LogicException('Cannot create stream for the file because the filesystem is not defined.');
        }

        $this->filesystem->createFileStream($this->key);
    }
}
