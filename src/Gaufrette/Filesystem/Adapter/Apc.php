<?php

namespace Gaufrette\Filesystem\Adapter;

use Gaufrette\Filesystem\Adapter;

/**
 * Apc adapter
 *
 * @package Gaufrette
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class Apc implements Adapter
{
    protected $ttl;

    /**
     * Constructor
     *
     * @param  integer $ttl Time to live
     */
    public function __construct($ttl = 0)
    {
        if (!extension_loaded('apc')) {
            throw new \RuntimeException(sprintf('The class \'%s\' requires the APC extension to be loaded.', __CLASS__));
        }

        $this->ttl = $ttl;
    }

    /**
     * {@InheritDoc}
     */
    public function read($key)
    {

    }

    /**
     * {@InheritDoc}
     */
    public function write($key, $content)
    {

    }

    /**
     * {@InheritDoc}
     */
    public function keys($pattern)
    {

    }

    /**
     * {@InheritDoc}
     */
    public function mtime($key)
    {

    }

    /**
     * {@InheritDoc}
     */
    public function delete($key)
    {

    }
}
