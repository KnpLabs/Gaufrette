<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use OpenCloud\Common\Exceptions\DeleteError;
use OpenCloud\ObjectStore\Resource\Container;
use OpenCloud\ObjectStore\Service;
use OpenCloud\Common\Exceptions\CreateUpdateError;
use OpenCloud\Common\Exceptions\ObjFetchError;

/**
 * OpenCloud adapter
 *
 * @package Gaufrette
 * @author  James Watson <james@sitepulse.org>
 */
class OpenCloud implements Adapter,
                           ChecksumCalculator
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

    public function __construct(
        Service $objectStore, $containerName, $createContainer = false, $detectContentType = true
    ) {
        $this->objectStore       = $objectStore;
        $this->containerName     = $containerName;
        $this->createContainer   = $createContainer;
        $this->detectContentType = $detectContentType;
    }

    private function initialize()
    {
        if (!$this->container instanceof Container) {

            if ($this->createContainer) {
                $container       = $this->objectStore->Container();
                $container->name = $this->containerName;
                $container->Create();
            } else {
                $container = $this->objectStore->Container($this->containerName);
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

        // This method can return boolean or the object
        // if there is a fetch error, tryGetObject returns false
        // If it returns false, php throws a fatal error because boolean is a non-object.
        $object = $this->tryGetObject($key);
        if ($object) {
            return $object->SaveToString();
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
            if ($object === false) {
                $object = $this->container->DataObject();
                $object->SetData($content);

                $data = array ('name' => $key);

                if ($this->detectContentType) {
                    $fileInfo             = new \finfo(FILEINFO_MIME_TYPE);
                    $contentType          = $fileInfo->buffer($content);
                    $data['content_type'] = $contentType;
                }

                $object->Create($data);
            }

            return $object->bytes;
        }
        catch (CreateUpdateError $updateError) {
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

        return ($this->tryGetObject($key) !== false);
    }

    /**
     * Returns an array of all keys (files and directories)
     *
     * @return array
     */
    public function keys()
    {
        $this->initialize();
        $objectList = $this->container->ObjectList();
        $keys       = array ();
        while ($object = $objectList->Next()) {
            $keys[] = $object->name;
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
            return $object->last_modified;
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
            $object->Delete();
        }
        catch (DeleteError $deleteError) {

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
        $this->write($targetKey, $this->read($sourceKey));
        $this->delete($sourceKey);
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
            return $object->getETag();
        }

        return false;
    }

    /**
     * @param $key
     * @return \OpenCloud\ObjectStore\Resource\DataObject
     */
    protected function tryGetObject($key)
    {
        try {
            return $this->container->DataObject($key);
        }
        catch (ObjFetchError $objFetchError) {
            return false;
        }
    }
}
