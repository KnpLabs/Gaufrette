<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\StorageFailure;
use Gaufrette\Exception\UnsupportedAdapterMethodException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Config;
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
        try {
            $result = $this->adapter->read($key);
        } catch (\Exception $e) {
            if ($e instanceof FileNotFoundException) {
                throw new FileNotFound($key, $e->getCode(), $e);
            }

            throw StorageFailure::unexpectedFailure('read', ['key' => $key], $e);
        }

        if (false === $result) {
            throw StorageFailure::unexpectedFailure('read', ['key' => $key]);
        }

        return $result['contents'];
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        try {
            $result = $this->adapter->write($key, $content, $this->config);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure(
                'write',
                ['key' => $key, 'content' => $content],
                $e
            );
        }

        if (false === $result) {
            throw StorageFailure::unexpectedFailure(
                'write',
                ['key' => $key, 'content' => $content]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        try {
            return (bool) $this->adapter->has($key);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('exists', ['key' => $key], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        try {
            return array_map(function ($content) {
                return $content['path'];
            }, $this->adapter->listContents());
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('keys', [], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = '')
    {
        $dirs = [];
        $keys = [];

        try {
            foreach ($this->adapter->listContents() as $content) {
                if (empty($prefix) || 0 === strpos($content['path'], $prefix)) {
                    if ('dir' === $content['type']) {
                        $dirs[] = $content['path'];
                    } else {
                        $keys[] = $content['path'];
                    }
                }
            }
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure(
                'listKeys',
                ['prefix' => $prefix],
                $e
            );
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
        try {
            $result = $this->adapter->getTimestamp($key);
        } catch (\Exception $e) {
            if ($e instanceof FileNotFoundException) {
                throw new FileNotFound($key, $e->getCode(), $e);
            }

            throw StorageFailure::unexpectedFailure('mtime', ['key' => $key], $e);
        }

        if (false === $result) {
            throw StorageFailure::unexpectedFailure('mtime', ['key' => $key]);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        try {
            $result = $this->adapter->delete($key);
        } catch (\Exception $e) {
            if ($e instanceof FileNotFoundException) {
                throw new FileNotFound($key, $e->getCode(), $e);
            }

            throw StorageFailure::unexpectedFailure('delete', ['key' => $key], $e);
        }

        if (false === $result) {
            throw StorageFailure::unexpectedFailure('delete', ['key' => $key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        try {
            $result = $this->adapter->rename($sourceKey, $targetKey);
        } catch (\Exception $e) {
            if ($e instanceof FileNotFoundException) {
                throw new FileNotFound($sourceKey, $e->getCode(), $e);
            }

            throw StorageFailure::unexpectedFailure(
                'rename',
                ['sourceKey' => $sourceKey, 'targetKey' => $targetKey],
                $e
            );
        }

        if (false === $result) {
            throw StorageFailure::unexpectedFailure(
                'rename',
                ['sourceKey' => $sourceKey, 'targetKey' => $targetKey]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
    {
        throw new UnsupportedAdapterMethodException('isDirectory is not supported by this adapter.');
    }
}
