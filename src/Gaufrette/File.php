<?php

namespace Gaufrette;

use Gaufrette\Exception\FileNotFound;

/**
 * Points to a file in a filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
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
     * @var array metadata in associative array. Only for adapters that support metadata
     */
    protected $metadata = null;

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
     * Constructor
     *
     * @param string     $key
     * @param Filesystem $filesystem
     */
    public function __construct($key)
    {
        $this->key = $key;
        $this->name = $key;
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
     * @param  array  $metadata optional metadata which should be send when read
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
     * @param array  $metadata optional metadata which should be send when write
     *
     * @return integer The number of bytes that were written into the file, or
     *                 FALSE on failure
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
     * @param int size of the file
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    public function getMimetype()
    {
        return $this->mimetype;
    }
    
    public function setMimetype($mimetype)
    {
        $this->mimetype = $mimetype;
    }
    
    /**
     * Get metadata array
     *
     * @return array metadata
     */
    public function getMetadata()
    {
        return isset($this->metadata) ? $this->metadata : null;
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
        
        return true;
    }

    
    /**
     * Get single metadata item
     *
     * @param string metaKey
     *
     * @return string value
     */
    public function getMetadataItem($metaKey)
    {
        if (! $this->supportsMetadata()) {
            throw new \RuntimeException("This adapter does not support metadata.");
        }
        return $this->metadata;        
    }
    
    /**
     * Add one metadata item to file (only if adapter supports metadata)
     * 
     * @param   string  $metaKey
     * @param   string  $metaValue
     * @throws  \RuntimeException when metaKey is already reserved
     *
     * @return  boolean success
     */
    public function setMetadataItem($metaKey, $metaValue)
    {
        if (isset($this->metadata[$metaKey])) {
            throw new \RuntimeException("Key '$metaKey' for metadata is already in use.");
        }
        $this->metadata[$metaKey] = $metaValue;
        
        return true;
    }

    /**
     * Creates a new file stream instance of the file
     *
     * @return FileStream
     */
    /*
    public function createStream()
    {
        return $this->filesystem->createStream($this->key);
    }
    */
    
}
