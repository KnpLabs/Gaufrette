<?php

namespace Gaufrette\Adapter;

use Gaufrette\Util;
use Gaufrette\Exception;

/**
 * In memory adapter
 *
 * Stores some files in memory for test purposes
 *
 * @package Gaufrette
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class InMemory extends Base
{
    protected $files = array();

    /**
     * Constructor
     *
     * @param  array $files An array of files
     */
    public function __construct(array $files = array())
    {
        $this->setFiles($files);
    }

    /**
     * Defines the files
     *
     * @param  array $files An array of files
     */
    public function setFiles(array $files)
    {
        $this->files = array();
        foreach ($files as $key => $file) {
            if (!is_array($file)) {
                $file = array('content' => $file);
            }

            $file = array_merge(array(
                'content'   => null,
                'mtime'     => null,
                'checksum'  => null
            ), $file);

            $this->setFile($key, $file['content'], $file['mtime'], $file['checksum']);
        }
    }

    /**
     * Defines a file
     *
     * @param  string  $key      The key
     * @param  string  $content  The content
     * @param  integer $mtime    The last modified time (automatically set to now if NULL)
     * @param  string  $checksum The checksum (automatically computed from the content if NULL)
     */
    public function setFile($key, $content = null, $mtime = null, $checksum = null)
    {
        if (null === $mtime) {
            $mtime = time();
        }

        if (null === $checksum) {
            $checksum = Util\Checksum::fromContent($content);
        }

        $this->files[$key] = array(
            'content'   => (string)  $content,
            'mtime'     => (integer) $mtime,
            'checksum'  => (string)  $checksum
        );
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $this->assertExists($key);

        return $this->files[$key]['content'];
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->assertExists($sourceKey);

        if ($this->exists($targetKey)) {
            throw new Exception\UnexpectedFile($targetKey);
        }

        $this->files[$targetKey] = $this->files[$sourceKey];
        unset($this->files[$sourceKey]);
        $this->files[$targetKey]['mtime'] = time();
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $this->files[$key]['content']  = $content;
        $this->files[$key]['mtime']    = time();
        $this->files[$key]['checksum'] = Util\Checksum::fromContent($content);

        return Util\Size::fromContent($content);
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return array_key_exists($key, $this->files);
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        return array_keys($this->files);
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        $this->assertExists($key);

        return $this->files[$key]['mtime'];
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        $this->assertExists($key);

        return $this->files[$key]['checksum'];
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->assertExists($key);

        unset($this->files[$key]);
        clearstatcache();

        return true;
    }

    protected function assertExists($key)
    {
        if (!$this->exists($key)) {
            throw new Exception\FileNotFound($key);
        }
    }
}
