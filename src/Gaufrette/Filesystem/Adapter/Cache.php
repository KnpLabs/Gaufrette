<?php

namespace Gaufrette\Filesystem\Adapter;

use Gaufrette\Adapter;

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

    /**
     * Constructor
     *
     * @param  Adapter $source
     * @param  Adapter $cache
     */
    public function __construct(Adapter $source, Adapter $cache)
    {
        $this->source = $source;
        $this->cache = $cache;
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
    public function write($key, $content)
    {
        $this->source->write($key, $content);
        $this->cache->write($key, $content);
    }

    /**
     * {@InheritDoc}
     */
    public function exists()
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
            return $this->cache->mtime($key) < $this->source->mtime($key);
        } catch (\RuntimeException $e) {
            return true;
        }
    }
}
