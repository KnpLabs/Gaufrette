<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\File;

/**
 * Cache adapter
 *
 * @package Gaufrette
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class Cache implements Adapter
{
    protected $source;
    protected $cache;
    protected $ttl;

    /**
     * Constructor
     *
     * @param  Adapter $source  The source adapter that must be cached
     * @param  Adapter $cache   The adapter used to cache the source
     * @param  integer $ttl     Time to live of a cached file
     */
    public function __construct(Adapter $source, Adapter $cache, $ttl = 0)
    {
        $this->source = $source;
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    /**
     * Returns the time to live of the cache
     *
     * @return integer $ttl
     */
    public function getTtl() {
        return $this->ttl;
    }

    /**
     * Defines the time to live of the cache
     *
     * @param  integer $ttl
     */
    public function setTtl($ttl) {
        $this->ttl = $ttl;
    }

    /**
     * {@InheritDoc}
     */
    public function read($key)
    {
        if ($this->needsReload($key)) {
            $this->cache->write($key, $this->source->read($key));
        }

        return $this->cache->read($key);
    }

    /**
     * {@InheritDoc}
     */
    public function rename($key, $new)
    {
        $this->source->rename($key, $new);
        $this->cache->rename($key, $new);
    }

    /**
     * {@InheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $this->source->write($key, $content);
        $this->cache->write($key, $content);
    }

    /**
     * {@InheritDoc}
     */
    public function exists($key)
    {
        return $this->source->exists($key);
    }

    /**
     * {@InheritDoc}
     */
    public function mtime($key)
    {
        return $this->source->mtime($key);
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        return $this->source->checksum($key);
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $cacheFile = '__keys.cache';
        if ($this->needsReload($cacheFile, false)) {
            $this->cache->write($cacheFile, serialize($this->source->keys()));
        }
        
        return unserialize($this->cache->read($cacheFile));
    }

    /**
     * Creates a new File instance and returns it
     *
     * @param  string $key
     * @return File
     */
    public function get($key, $filesystem)
    {
        if (is_callable(array($this->source, 'get'))) {
            //If possible, delegate getting the file object to the source adapter.
            return $this->source->get($key, $filesystem);
        }

        return new File($key, $filesystem);
    }
    
    /**
     * @return array
     */
    public function listDirectory($directory = '')
    {
        if (method_exists($this->source, 'listDirectory')) {
            $cacheFile = '__listDirectory-' . md5($directory) . '.cache';
            
            if ($this->needsReload($cacheFile, false)) {
                $this->cache->write($cacheFile, serialize($this->source->listDirectory($directory)));
            }

            return unserialize($this->cache->read($cacheFile));
        }
        else {
            return null;
        }
    }

    /**
     * {@InheritDoc}
     */
    public function delete($key)
    {
        $this->source->delete($key);
        $this->cache->delete($key);
    }

    /**
     * Indicates whether the cache for the specified key needs to be reloaded
     *
     * @param  string $key
     * @param  boolean $checkSource
     */
    public function needsReload($key, $checkSource = true)
    {
        if (!$this->cache->exists($key)) {
            return true;
        }

        try {
            $dateCache = $this->cache->mtime($key);
            
            if (time() - $this->ttl > $dateCache) {
                $dateSource = $this->source->mtime($key);

                return $checkSource ? $dateCache < $dateSource : false;
            }
            else {
                return false;
            }
        } catch (\RuntimeException $e) {
            return true;
        }
    }

    /**
     * {@InheritDoc}
     */
    public function supportsMetadata()
    {
        return false;
    }
}
