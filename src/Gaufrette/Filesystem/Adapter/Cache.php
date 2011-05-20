<?php

namespace Gaufrette\Filesystem\Adapter;

use Gaufrette\Filesystem\Adapter;

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
    public function write($key, $content)
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
        return $this->source->keys();
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
            $dateSource = $this->source->mtime($key);

            return time() - $this->ttl > $dateCache && $dateCache < $dateSource;
        } catch (\RuntimeException $e) {
            return true;
        }
    }
}
