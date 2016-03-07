<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Exception\UnsupportedAdapterMethodException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Util;

class Flysystem implements Adapter, ListKeysAware
{
    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var Config
     */
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
        return array_map(function ($content) {
            return $content['path'];
        }, $this->adapter->listContents());
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = '')
    {
        $dirs = [];
        $keys = [];

        foreach ($this->adapter->listContents() as $content) {
            if (empty($prefix) || 0 === strpos($content['path'], $prefix)) {
                if ('dir' === $content['type']) {
                    $dirs[] = $content['path'];
                } else {
                    $keys[] = $content['path'];
                }
            }
        }

        return [
            'keys' => $keys,
            'dirs' => $dirs,
        ];
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
        throw new UnsupportedAdapterMethodException('isDirectory is not supported by this adapter.');
    }
}
