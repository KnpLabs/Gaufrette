<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Exception\UnsupportedAdapterMethodException;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Util;
use League\Flysystem\Config;

class Flysystem implements Adapter, ListKeysAware
{
    private Config $config;

    /**
     * @param Config|array|null $config
     */
    public function __construct(private readonly AdapterInterface $adapter, $config = null)
    {
        if (!interface_exists(AdapterInterface::class)) {
            throw new \LogicException('You need to install package "league/flysystem" to use this adapter');
        }

        $this->config = Util::ensureConfig($config);
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|bool
    {
        return $this->adapter->read($key)['contents'];
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, mixed $content): int|bool
    {
        return $this->adapter->write($key, $content, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return (bool) $this->adapter->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        return array_map(function ($content) {
            return $content['path'];
        }, $this->adapter->listContents());
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(string $prefix = ''): array
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
    public function mtime(string $key): int|bool
    {
        return $this->adapter->getTimestamp($key);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return $this->adapter->delete($key);
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        return $this->adapter->rename($sourceKey, $targetKey);
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        throw new UnsupportedAdapterMethodException('isDirectory is not supported by this adapter.');
    }
}
