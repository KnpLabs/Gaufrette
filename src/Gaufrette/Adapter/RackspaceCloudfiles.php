<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Util;
use \CF_Container as RackspaceContainer;

/**
 * Rackspace cloudfiles adapter
 *
 * @package Gaufrette
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 * @deprecated php-cloudfiles is deprecated and will be unavailable after August 1, 2013
 */
class RackspaceCloudfiles implements Adapter,
                                     ChecksumCalculator
{
    protected $container;

    /**
     * Constructor
     *
     * @param RackspaceContainer $container A CF_Container instance
     */
    public function __construct(RackspaceContainer $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     *
     * @throws \InvalidResponseException
     */
    public function read($key)
    {
         $object = $this->container->get_object($key);

         return $object->read();
    }

    /**
     * {@inheritDoc}
     */
    public function rename($key, $new)
    {
       $this->write($new, $this->read($key));
       $this->delete($key);

       return true;
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $object = $this->tryGetObject($key);
        if (false === $object) {
            // the object does not exist, so we create it
            $object = $this->container->create_object($key);
        }

        if (!$object->write($content)) {
            return false;
        }

        return Util\Size::fromContent($content);
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return false !== $this->tryGetObject($key);
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $keys = $this->container->list_objects(0, null, null);
        sort($keys);

        return $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        $object = $this->container->get_object($key);

        return $object->getETag();
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidResponseException
     * @throws SyntaxException
     */
    public function delete($key)
    {
        try {
            $this->container->delete_object($key);
        } catch (\NoSuchObjectException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isDirectory($key)
    {
        return false;
    }

    /**
     * Tries to get the object for the specified key or return false
     *
     * @param string $key The key of the object
     *
     * @return CF_Object or FALSE if the object does not exist
     */
    protected function tryGetObject($key)
    {
        try {
            return $this->container->get_object($key);
        } catch (\NoSuchObjectException $e) {
            // the NoSuchObjectException is thrown by the CF_Object during it's
            // creation if the object doesn't exist
            return false;
        }
    }
}
