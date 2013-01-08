<?php
namespace Gaufrette\File;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\File;
use \MongoGridFSFile;

/**
 * Points to a file in a filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class GridFS extends File
{
    private $gridFSFile = null;
    
    /**
     * Constructor
     *
     * @param string     $key
     * @param Filesystem $filesystem
     */
    public function __construct($key, MongoGridFSFile $gridFSFile = null)
    {
        $this->key = $key;
        $this->gridFSFile = $gridFSFile;
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
        if (isset($this->content)) {
            return $this->content;
        }
        return $this->gridFSFile->getBytes();
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
