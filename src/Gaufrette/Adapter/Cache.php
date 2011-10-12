<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\File;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Adapter\InMemory as InMemoryAdapter;

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
    protected $serializeCache;

    /**
     * Constructor
     *
     * @param  Adapter $source  The source adapter that must be cached
     * @param  Adapter $cache   The adapter used to cache the source
     * @param  integer $ttl     Time to live of a cached file
     * @param  null|Adapter|string $serializeCache     The adapter used to cache serializations
     */
    public function __construct(Adapter $source, Adapter $cache, $ttl = 0, $serializeCache = null)
    {
        $this->source = $source;
        $this->cache = $cache;
        $this->ttl = $ttl;
        
        if ($serializeCache instanceof Adapter) {
            $this->serializeCache = $serializeCache;
        }
        elseif (is_string($serializeCache)) {
            $this->serializeCache = new LocalAdapter($serializeCache, true);
        }
        else {
            $this->serializeCache = new InMemoryAdapter();
        }
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
        $cacheFile = '.keys.cache';
        if ($this->needsRebuild($cacheFile)) {
            $this->serializeCache->write($cacheFile, serialize($this->source->keys()));
        }
        
        return unserialize($this->serializeCache->read($cacheFile));
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
            $cacheFile = '.dir-' . md5($directory) . '.cache';
            
            if ($this->needsRebuild($cacheFile)) {
                $this->serializeCache->write($cacheFile, serialize($this->source->listDirectory($directory)));
            }

            return unserialize($this->serializeCache->read($cacheFile));
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
     */
    public function needsReload($key)
    {
        if (!$this->cache->exists($key)) {
            return true;
        }

        try {
            $dateCache = $this->cache->mtime($key);
            
            if (time() - $this->ttl > $dateCache) {
                $dateSource = $this->source->mtime($key);

                return $dateCache < $dateSource;
            }
            else {
                return false;
            }
        } catch (\RuntimeException $e) {
            return true;
        }
    }

    /**
     * Indicates whether the serialized cache file needs to be rebuild
     *
     * @param  string $cacheFile
     */
    public function needsRebuild($cacheFile)
    {
        if (!$this->serializeCache->exists($key)) {
            return true;
        }

        try {
            $dateCache = $this->serializeCache->mtime($key);
            
            if (time() - $this->ttl > $dateCache) {
                return true;
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
