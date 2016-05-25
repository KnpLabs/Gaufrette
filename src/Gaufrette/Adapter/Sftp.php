<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Ssh\Sftp as SftpClient;

class Sftp implements Adapter,
                      ChecksumCalculator
{
    protected $sftp;
    protected $directory;
    protected $create;
    protected $initialized = false;

    /**
     * @param \Ssh\Sftp $sftp      An Sftp instance
     * @param string    $directory The distant directory
     * @param bool      $create    Whether to create the remote directory if it
     *                             does not exist
     */
    public function __construct(SftpClient $sftp, $directory = null, $create = false)
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
        $this->initialize();

        $content = $this->sftp->read($this->computePath($key));

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
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
        $numBytes = $this->sftp->write($path, $content);

        return $numBytes;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        $this->initialize();

        $url = $this->sftp->getUrl($this->computePath($key));
        clearstatcache();

        return file_exists($url);
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
    {
        $this->initialize();

        $url = $this->sftp->getUrl($this->computePath($key));

        return is_dir($url);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        $this->initialize();
        $results = $this->sftp->listDirectory($this->directory, true);
        $files = array_map(array($this, 'computeKey'), $results['files']);

        $dirs = array();
        foreach ($files as $file) {
            if ('.' !== $dirname = \Gaufrette\Util\Path::dirname($file)) {
                $dirs[] = $dirname;
            }
        }

        $keys = array_merge($files, $dirs);
        sort($keys);

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        $this->initialize();

        return filemtime($this->sftp->getUrl($this->computePath($key)));
    }

    /**
     * {@inheritdoc}
     */
    public function checksum($key)
    {
        $this->initialize();

        if ($this->exists($key)) {
            return md5_file($this->sftp->getUrl($this->computePath($key)));
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $this->initialize();

        return unlink($this->sftp->getUrl($this->computePath($key)));
    }

    /**
     * Computes the key from the specified path.
     *
     * @param string $path
     *
     * @return string
     */
    public function computeKey($path)
    {
        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The path \'%s\' is out of the filesystem.', $path));
        }

        return ltrim(substr($path, strlen($this->directory)), '/');
    }

    /**
     * Computes the path for the specified key.
     *
     * @param string $key
     *
     * @return string
     */
    protected function computePath($key)
    {
        return $this->directory.'/'.ltrim($key, '/');
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

    /**
     * Ensures the specified directory exists.
     *
     * @param string $directory The directory that we ensure the existence
     * @param bool   $create    Whether to create it if it does not exist
     *
     * @throws RuntimeException if the specified directory does not exist and
     *                          could not be created
     */
    protected function ensureDirectoryExists($directory, $create = false)
    {
        $url = $this->sftp->getUrl($directory);

        $resource = @opendir($url);
        if (false === $resource && (!$create || !$this->createDirectory($directory))) {
            throw new \RuntimeException(sprintf('The directory \'%s\' does not exist and could not be created.', $directory));
        }

        // make sure we don't leak the resource
        if (is_resource($resource)) {
            closedir($resource);
        }
    }

    /**
     * Creates the specified directory and its parents.
     *
     * @param string $directory The directory to create
     *
     * @return bool TRUE on success, or FALSE on failure
     */
    protected function createDirectory($directory)
    {
        return $this->sftp->mkdir($directory, 0777, true);
    }
}
