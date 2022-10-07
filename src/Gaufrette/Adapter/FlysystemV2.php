<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Exception\UnsupportedAdapterMethodException;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToWriteFile;

class FlysystemV2 implements Adapter, ListKeysAware
{
    /**
     * @var FilesystemAdapter
     */
    private $adapter;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param FilesystemAdapter  $adapter
     * @param Config|array|null $config
     */
    public function __construct(FilesystemAdapter $adapter, $config = null)
    {
        if (!interface_exists(FilesystemAdapter::class)) {
            throw new \LogicException('You need to install package "league/flysystem" to use this adapter');
        }

        $this->adapter = $adapter;
        $this->config = $this->ensureConfig($config);
    }

    /**
     * {@inheritdoc}
     */
    public function read($key)
    {
        return $this->adapter->read($key);
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        try {
            $this->adapter->write($key, $content, $this->config);

            return $this->adapter->fileSize($key)->fileSize();
        } catch (UnableToWriteFile $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        return $this->adapter->fileExists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return array_map(function (StorageAttributes $content) {
            return $content->path();
        }, $this->adapter->listContents('', true));
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = '')
    {
        $dirs = [];
        $keys = [];

        foreach ($this->adapter->listContents('', true) as $content) {
            if (empty($prefix) || 0 === strpos($content->path(), $prefix)) {
                if ($content->isDir()) {
                    $dirs[] = $content->path();
                } else {
                    $keys[] = $content->path();
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
        return $this->adapter->lastModified($key)->lastModified();
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        try {
            $this->adapter->delete($key);
        } catch (UnableToDeleteFile $exception) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        try {
            $this->adapter->move($sourceKey, $targetKey, $this->config);
        } catch (UnableToMoveFile $exception) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
    {
        throw new UnsupportedAdapterMethodException('isDirectory is not supported by this adapter.');
    }

    /**
     * Ensure a Config instance.
     *
     * @param null|array|Config $config
     *
     * @return Config config instance
     *
     * @throw  LogicException
     */
    private function ensureConfig($config)
    {
        if ($config === null) {
            return new Config();
        }

        if ($config instanceof Config) {
            return $config;
        }

        if (is_array($config)) {
            return new Config($config);
        }

        throw new \LogicException('A config should either be an array or a League\Flysystem\Config object.');
    }
}
