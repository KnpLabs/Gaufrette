<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Adapter\ChecksumCalculator;
use OpenStack\ObjectStore\v1\Resource\Object;
use OpenStack\ObjectStore\v1\ObjectStorage;
use OpenStack\ObjectStore\v1\Resource\Container;
use OpenStack\Common\Exception;

/**
 * OpenStack adapter
 *
 * @author Gaultier Boniface <gboniface@wysow.fr>
 */
class OpenStack implements Adapter, ChecksumCalculator
{
    /**
     * @var ObjectStore
     */
    protected $objectStore;

    /**
     * @var string
     */
    protected $containerName;

    /**
     * @var bool
     */
    protected $createContainer;

    /**
     * @var bool
     */
    protected $detectContentType;

    /**
     * @var Container
     */
    protected $container;

    public function __construct(ObjectStorage $objectStore, $containerName = 'default', $createContainer = false, $detectContentType = true)
    {
        $this->objectStore       = $objectStore;
        $this->containerName     = $containerName;
        $this->createContainer   = $createContainer;
        $this->detectContentType = $detectContentType;
    }

    private function initialize()
    {
        if (!$this->container instanceof Container) {
            if ($this->createContainer) {
                $this->objectStore->createContainer($this->containerName);
                $container = $this->objectStore->container($this->containerName);
            } else {
                $container = $this->objectStore->container($this->containerName);
            }
            $this->container = $container;
        }
    }

    /**
     * Reads the content of the file
     *
     * @param string $key
     *
     * @return string|boolean if cannot read content
     */
    public function read($key)
    {
        $this->initialize();

        $object = $this->tryGetObject($key);
        if ($object) {
            return $object->content();
        }

        return $object;
    }

    /**
     * Writes the given content into the file
     *
     * @param string $key
     * @param string $content
     *
     * @return integer|boolean The number of bytes that were written into the file
     */
    public function write($key, $content)
    {
        $this->initialize();
        $object = $this->tryGetObject($key);

        try {
            if ($object === null) {
                $object = new Object($key, $content);
            } else {
                $object->setContent($content);
            }

            if ($this->detectContentType) {
                $fileInfo             = new \finfo(FILEINFO_MIME_TYPE);
                $contentType          = $fileInfo->buffer($content);
                $object->setContentType($contentType);
            }

            $this->container->save($object);

            return $object->contentLength();
        } catch (Exception $writeError) {
            return false;
        }
    }

    /**
     * Indicates whether the file exists
     *
     * @param string $key
     *
     * @return boolean
     */
    public function exists($key)
    {
        $this->initialize();

        return ($this->tryGetObject($key) !== null);
    }

    /**
     * Returns an array of all keys (files and directories)
     *
     * @return array
     */
    public function keys()
    {
        $this->initialize();

        $objects = $this->container->objects();
        $keys = array();
        foreach ($objects as $object) {
            $keys[] = $object->name();
        }
        sort($keys);

        return $keys;
    }

    /**
     * Returns the last modified time
     *
     * @param string $key
     *
     * @return integer|boolean An UNIX like timestamp or false
     */
    public function mtime($key)
    {
        $this->initialize();

        $object = $this->tryGetObject($key);

        if ($object) {
            return $object->lastModified();
        }

        return false;
    }

    /**
     * Deletes the file
     *
     * @param string $key
     *
     * @return boolean
     */
    public function delete($key)
    {
        $this->initialize();

        try {
            $object = $this->tryGetObject($key);
            if (!$object) {
                return false;
            }
            $this->container->delete($key);
        } catch (Exception $deleteError) {
            return false;
        }

        return true;
    }

    /**
     * Renames a file
     *
     * @param string $sourceKey
     * @param string $targetKey
     *
     * @return boolean
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->initialize();

        if ($this->write($targetKey, $this->read($sourceKey))) {
            return $this->delete($sourceKey);
        }

        return false;
    }

    /**
     * Check if key is directory
     *
     * @param string $key
     *
     * @return boolean
     */
    public function isDirectory($key)
    {
        return false;
    }

    /**
     * Returns the checksum of the specified key
     *
     * @param string $key
     *
     * @return string
     */
    public function checksum($key)
    {
        $this->initialize();

        $object = $this->tryGetObject($key);
        if ($object) {
            return $object->eTag();
        }

        return false;
    }

    /**
     * Returns the content type of the specified key
     *
     * @param string $key
     *
     * @return string
     */
    public function contentType($key)
    {
        $this->initialize();

        $object = $this->tryGetObject($key);
        if ($object) {
            return $object->contentType();
        }

        return false;
    }

    /**
     * @param string $key
     * @return \OpenStack\OpenStack\ObjectStore\v1\Resource\RemoteObject or null
     */
    protected function tryGetObject($key)
    {
        try {
            return $this->container->proxyObject($key);
        } catch (\OpenStack\Common\Exception $objFetchError) {
            return null;
        }
    }
}
