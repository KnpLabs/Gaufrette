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
 */
class RackspaceCloudfiles implements Adapter,
                                     ChecksumCalculator
{
    protected $container;

    protected $purgeOnOverwrite;

    /**
     * Constructor
     *
     * @param RackspaceContainer $container        A CF_Container instance
     * @param bool               $purgeOnOverwrite if <code>true</code> will purge the cdn automatically when overwriting a file
     */
    public function __construct(RackspaceContainer $container, $purgeOnOverwrite = true)
    {
        $this->container = $container;
        $this->purgeOnOverwrite = $purgeOnOverwrite;
    }

    /**
     * Sets purge on overwrite
     *
     * @param bool $purgeOnOverwrite
     */
    public function setPurgeOnOverwrite($purgeOnOverwrite)
    {
        $this->purgeOnOverwrite = $purgeOnOverwrite;
    }

    /**
     * Gets purge on overwrite
     *
     * @return bool
     */
    public function getPurgeOnOverwrite()
    {
        return $this->purgeOnOverwrite;
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
        $new = false;
        if (false === $object) {
            // the object does not exist, so we create it
            $new = true;
            $object = $this->container->create_object($key);
        }

        if (!$object->write($content)) {
            return false;
        }

        // checks if needs to purge the object from the cdn
        if (!$new && $this->purgeOnOverwrite && $this->container->cdn_enabled) {
            $object->purge_from_cdn();
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
