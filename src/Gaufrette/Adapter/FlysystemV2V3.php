<?php
declare(strict_types=1);

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Exception\UnsupportedAdapterMethodException;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToWriteFile;
use Throwable;

/**
 * @internal
 */
class FlysystemV2V3 implements Adapter, ListKeysAware
{
    private Config $config;

    private FilesystemAdapter $adapter;

    /**
     * @param mixed $config
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

            return $this->adapter->fileSize($key)->fileSize() ?? false;
        } catch (UnableToWriteFile $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key): bool
    {
        return $this->adapter->fileExists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        $content = $this->adapter->listContents('', true);
        if (!is_array($content)) {
            return [];
        }

        return array_map(fn (StorageAttributes $content) => $content->path(), $content);
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = ''): array
    {
        $dirs = [];
        $keys = [];

        foreach ($this->adapter->listContents('', true) as $content) {
            if (empty($prefix) || str_starts_with($content->path(), $prefix)) {
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
        try {
            return $this->adapter->lastModified($key)->lastModified() ?? false;
        } catch (Throwable $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key): bool
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
    public function rename($sourceKey, $targetKey): bool
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
    public function isDirectory($key): bool
    {
        throw new UnsupportedAdapterMethodException('isDirectory is not supported by this adapter.');
    }

    /**
     * Ensure a Config instance.
     * @param mixed $config
     * @throw  LogicException
     */
    private function ensureConfig($config): Config
    {
        if (null === $config) {
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
