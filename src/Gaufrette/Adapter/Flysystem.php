<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Util;

class Flysystem implements Adapter
{
    private $adapter;
    private $config;

    /**
     * @param AdapterInterface  $adapter
     * @param Config|array|null $config
     */
    public function __construct(AdapterInterface $adapter, $config = null)
    {
        $this->adapter = $adapter;
        $this->config = Util::ensureConfig($config);
    }

    /**
     * {@inheritdoc}
     */
    public function read($key)
    {
        // TODO: handle false returned
        return $this->adapter->read($key)['contents'];
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        return $this->adapter->write($key, $content, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        return $this->adapter->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return array_map(function($content) {
            return $content['path'];
        }, $this->adapter->listContents());
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        return $this->adapter->getTimestamp($key);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->adapter->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        return $this->adapter->rename($sourceKey, $targetKey);
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
    {
        // TODO: create proper exception class if Flysistem does not support isDirectory()
        throw new \BadMethodCallException('isDirectory is not supported by this adapter.');
    }
}
