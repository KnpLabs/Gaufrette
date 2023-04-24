<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use phpseclib\Net\SFTP as SecLibSFTP;
use Gaufrette\Filesystem;
use Gaufrette\File;

class PhpseclibSftp implements Adapter, FileFactory, ListKeysAware
{
    protected bool $initialized = false;

    /**
     * @param string      $directory The distant directory
     * @param bool        $create    Whether to create the remote directory if it
     *                               does not exist
     */
    public function __construct(
        private readonly SecLibSFTP $sftp,
        private readonly ?string $directory = null,
        private readonly bool $create = false
    ) {
        if (!class_exists(SecLibSFTP::class)) {
            throw new \LogicException('You need to install package "phpseclib/phpseclib" to use this adapter');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|bool
    {
        return $this->sftp->get($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        $this->initialize();

        $sourcePath = $this->computePath($sourceKey);
        $targetPath = $this->computePath($targetKey);

        $this->ensureDirectoryExists(\Gaufrette\Util\Path::dirname($targetPath), true);

        return $this->sftp->rename($sourcePath, $targetPath);
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, mixed $content): int|bool
    {
        $this->initialize();

        $path = $this->computePath($key);
        $this->ensureDirectoryExists(\Gaufrette\Util\Path::dirname($path), true);
        if ($this->sftp->put($path, $content)) {
            return $this->sftp->size($path);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        $this->initialize();

        return false !== $this->sftp->stat($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        $this->initialize();

        $pwd = $this->sftp->pwd();
        if ($this->sftp->chdir($this->computePath($key))) {
            $this->sftp->chdir($pwd);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        $keys = $this->fetchKeys();

        return $keys['keys'];
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(string $prefix = ''): array
    {
        preg_match('/(.*?)[^\/]*$/', $prefix, $match);
        $directory = rtrim($match[1], '/');

        $keys = $this->fetchKeys($directory, false);

        if ($directory === $prefix) {
            return $keys;
        }

        $filteredKeys = [];
        foreach (['keys', 'dirs'] as $hash) {
            $filteredKeys[$hash] = [];
            foreach ($keys[$hash] as $key) {
                if (0 === strpos($key, $prefix)) {
                    $filteredKeys[$hash][] = $key;
                }
            }
        }

        return $filteredKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|bool
    {
        $this->initialize();

        $stat = $this->sftp->stat($this->computePath($key));

        return $stat['mtime'] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return $this->sftp->delete($this->computePath($key), false);
    }

    /**
     * {@inheritdoc}
     */
    public function createFile(string $key, Filesystem $filesystem): File
    {
        $file = new File($key, $filesystem);

        $stat = $this->sftp->stat($this->computePath($key));
        if (isset($stat['size'])) {
            $file->setSize($stat['size']);
        }

        return $file;
    }

    /**
     * Performs the adapter's initialization.
     *
     * It will ensure the root directory exists
     */

    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->ensureDirectoryExists($this->directory, $this->create);

        $this->initialized = true;
    }

    protected function ensureDirectoryExists(string $directory, bool $create)
    {
        $pwd = $this->sftp->pwd();
        if ($this->sftp->chdir($directory)) {
            $this->sftp->chdir($pwd);
        } elseif ($create) {
            if (!$this->sftp->mkdir($directory, 0777, true)) {
                throw new \RuntimeException(sprintf('The directory \'%s\' does not exist and could not be created (%s).', $this->directory, $this->sftp->getLastSFTPError()));
            }
        } else {
            throw new \RuntimeException(sprintf('The directory \'%s\' does not exist.', $this->directory));
        }
    }

    protected function computePath(string $key): string
    {
        return $this->directory . '/' . ltrim($key, '/');
    }

    /**
     * @return array<string, array<string>>
     */
    protected function fetchKeys(string $directory = '', bool $onlyKeys = true): array
    {
        $keys = ['keys' => [], 'dirs' => []];
        $computedPath = $this->computePath($directory);

        if (!$this->sftp->file_exists($computedPath)) {
            return $keys;
        }

        $list = $this->sftp->rawlist($computedPath);
        foreach ((array) $list as $filename => $stat) {
            if ('.' === $filename || '..' === $filename) {
                continue;
            }

            $path = ltrim($directory . '/' . $filename, '/');
            if (isset($stat['type']) && $stat['type'] === NET_SFTP_TYPE_DIRECTORY) {
                $keys['dirs'][] = $path;
            } else {
                $keys['keys'][] = $path;
            }
        }

        $dirs = $keys['dirs'];

        if ($onlyKeys && !empty($dirs)) {
            $keys['keys'] = array_merge($keys['keys'], $dirs);
            $keys['dirs'] = [];
        }

        foreach ($dirs as $dir) {
            $keys = array_merge_recursive($keys, $this->fetchKeys($dir, $onlyKeys));
        }

        return $keys;
    }
}
