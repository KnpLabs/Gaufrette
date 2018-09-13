<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use phpseclib\Net\SFTP as SecLibSFTP;
use Gaufrette\Filesystem;
use Gaufrette\File;

class PhpseclibSftp implements Adapter,
                               FileFactory,
                               ListKeysAware
{
    protected $sftp;
    protected $directory;
    protected $create;
    protected $initialized = false;

    /**
     * @param SecLibSFTP  $sftp      An Sftp instance
     * @param string      $directory The distant directory
     * @param bool        $create    Whether to create the remote directory if it
     *                               does not exist
     */
    public function __construct(SecLibSFTP $sftp, $directory = null, $create = false)
    {
        $this->sftp = $sftp;
        $this->directory = $directory;
        $this->create = $create;
    }

    /**
     * {@inheritdoc}
     */
    public function read($key)
    {
        return $this->sftp->get($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
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
    public function write($key, $content)
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
    public function exists($key)
    {
        $this->initialize();

        return false !== $this->sftp->stat($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
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
    public function keys()
    {
        $keys = $this->fetchKeys();

        return $keys['keys'];
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = '')
    {
        preg_match('/(.*?)[^\/]*$/', $prefix, $match);
        $directory = rtrim($match[1], '/');

        $keys = $this->fetchKeys($directory, false);

        if ($directory === $prefix) {
            return $keys;
        }

        $filteredKeys = array();
        foreach (array('keys', 'dirs') as $hash) {
            $filteredKeys[$hash] = array();
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
    public function mtime($key)
    {
        $this->initialize();

        $stat = $this->sftp->stat($this->computePath($key));

        return isset($stat['mtime']) ? $stat['mtime'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->sftp->delete($this->computePath($key), false);
    }

    /**
     * {@inheritdoc}
     */
    public function createFile($key, Filesystem $filesystem)
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
    protected function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $this->ensureDirectoryExists($this->directory, $this->create);

        $this->initialized = true;
    }

    protected function ensureDirectoryExists($directory, $create)
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

    protected function computePath($key)
    {
        return $this->directory.'/'.ltrim($key, '/');
    }

    protected function fetchKeys($directory = '', $onlyKeys = true)
    {
        $keys = array('keys' => array(), 'dirs' => array());
        $computedPath = $this->computePath($directory);

        if (!$this->sftp->file_exists($computedPath)) {
            return $keys;
        }

        $list = $this->sftp->rawlist($computedPath);
        foreach ((array) $list as $filename => $stat) {
            if ('.' === $filename || '..' === $filename) {
                continue;
            }

            $path = ltrim($directory.'/'.$filename, '/');
            if (isset($stat['type']) && $stat['type'] === NET_SFTP_TYPE_DIRECTORY) {
                $keys['dirs'][] = $path;
            } else {
                $keys['keys'][] = $path;
            }
        }

        $dirs = $keys['dirs'];

        if ($onlyKeys && !empty($dirs)) {
            $keys['keys'] = array_merge($keys['keys'], $dirs);
            $keys['dirs'] = array();
        }

        foreach ($dirs as $dir) {
            $keys = array_merge_recursive($keys, $this->fetchKeys($dir, $onlyKeys));
        }

        return $keys;
    }
}
