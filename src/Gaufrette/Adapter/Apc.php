<?php

namespace Gaufrette\Adapter;

use Gaufrette\Checksum;

/**
 * Apc adapter, a non-persistent adapter for when this sort of thing is appropriate
 *
 * @package Gaufrette
 * @author  Alexander Deruwe <alexander.deruwe@gmail.com>
 */
class Apc extends Base
{
    protected $prefix;
    protected $ttl;

    /**
     * Constructor
     *
     * @throws \RuntimeException
     * @param string $prefix to avoid conflicts between filesystems
     * @param int $ttl time to live, default is 0
     */
    public function __construct($prefix, $ttl = 0)
    {
        if (!extension_loaded('apc')) {
            throw new \RuntimeException('Unable to use Gaufrette\\Adapter\\Apc as the APC extension is not enabled.');
        }

        $this->prefix = $prefix;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $object = $this->fetchObject($key);

        return $object['content'];
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $object = array(
            'content'   => $content,
            'checksum'  => Checksum::fromContent($content),
            'mtime'     => time(),
        );

        return $this->storeObject($key, $object);
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return apc_exists($this->computePath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $cachedKeys = new \APCIterator('user', sprintf('/^%s/', preg_quote($this->prefix)), APC_ITER_NONE);

        if (null === $cachedKeys) {
            throw new \RuntimeException('Could not get the keys.');
        }

        $keys = array();
        foreach (iterator_to_array($cachedKeys) as $key => $value) {
            $keys[] = str_replace($this->prefix, '', $key);
        }

        return $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        $object = $this->fetchObject($key);

        return $object['mtime'];
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        $object = $this->fetchObject($key);

        return $object['checksum'];
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $result = apc_delete($this->computePath($key));

        if (false === $result) {
            throw new \RuntimeException(sprintf('Could not delete the \'%s\' file.', $key));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rename($key, $new)
    {
        try {
            $object = $this->fetchObject($key);
            $object['mtime'] = time();

            $this->storeObject($new, $object);
            $this->delete($key);
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Could not rename the \'%s\' file to \'%s\'.', $key, $new));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supportsMetadata()
    {
        return false;
    }

    /**
     * Computes the path for the given key
     *
     * @param string $key
     * @return string
     */
    public function computePath($key)
    {
        return $this->prefix . $key;
    }

    /**
     * Fetch object from APC
     *
     * @throws \RuntimeException
     * @param string $key
     * @return array
     */
    public function fetchObject($key)
    {
        $object = apc_fetch($this->computePath($key));

        if (false === $object) {
            throw new \RuntimeException(sprintf('Could not read the \'%s\' file.', $key));
        }

        return $object;
    }

    /**
     * Store object in APC
     *
     * @throws \RuntimeException
     * @param $key
     * @param $object
     * @return int
     */
    public function storeObject($key, $object)
    {
        $result = apc_store($this->computePath($key), $object, $this->ttl);

        if (false === $result) {
            throw new \RuntimeException(sprintf('Could not write the \'%s\' file.', $key));
        }

        return mb_strlen($object);
    }
}
