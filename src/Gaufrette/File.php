<?php

namespace Gaufrette;

use Gaufrette\Exception\FileNotFound;

/**
 * Points to a file in a filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 */
class File
{
    protected $key;

    /**
     * Content variable is lazy. It will not be read from filesystem until it's requested first time
     * @var content
     */
    protected $content = null;

    /**
     * Human readable filename (usually the end of the key)
     * @var string name
     */
    protected $name = null;

    /**
     * File size in bytes
     * @var int size
     */
    protected $size = 0;

    /**
     * File mimetype
     * @var string mimetype
     */
    protected $mimetype = "";

    /**
     * Unix Timestamp that is altered whenever file is created or updated.
     * @var int timestamp
     */
    protected $timestamp;

    /**
     * MD5 checksum of the file content
     * @var string checksum
     */
    protected $checksum;

    /**
     * Metadata in associative array. Only for adapters that support metadata
     * @var array metadata
     */
    protected $metadata = array();

    /**
     * File date modified
     * @var int mtime
     */
    protected $mtime = null;

    /**
     * Constructor
     *
     * @param string     $key
     */
    public function __construct($key)
    {
        $this->key = $key;
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
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the content
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string name of the file
     */
    public function getName()
    {
        if (! isset($this->name)) {
            return $this->key;
        }
        return $this->name;
    }

    /**
     * @param string name of the file
     */
    public function setName($name)
    {
        $this->name = $name;
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
            return $this->size = Util\Size::fromContent($this->getContent());
        } catch (FileNotFound $exception) {
        }

        return 0;
    }

    /**
     * Returns the file modified time
     *
     * @return int
     */
    public function getMtime()
    {
        return $this->mtime = $this->filesystem->mtime($this->key);
    }

    /**
     * @param int size of the file
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getMimeType()
    {
        return $this->mimetype;
    }

    public function setMimeType($mimetype)
    {
        $this->mimetype = $mimetype;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function getChecksum()
    {
        return $this->checksum;
    }

    public function setChecksum($checksum)
    {
        $this->checksum = $checksum;
    }

    /**
     * Get metadata array
     *
     * @return array metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Sets the metadata array to be stored in adapters that can support it
     *
     * @param  array   $metadata
     * @return boolean
     */
    public function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;
    }


    /**
     * Get single metadata item
     *
     * @param string metaKey
     *
     * @return string value, null if key does not exist
     */
    public function getMetadataItem($metaKey)
    {
        if (isset($this->metadata[$metaKey])) {
            return $this->metadata[$metaKey];
        }
        return null;
    }

    /**
     * Add one metadata item to file (only if adapter supports metadata)
     *
     * @param   string  $metaKey
     * @param   string  $metaValue
     */
    public function setMetadataItem($metaKey, $metaValue)
    {
        $this->metadata[$metaKey] = $metaValue;
    }
}
