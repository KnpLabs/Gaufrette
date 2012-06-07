<?php

namespace Gaufrette\Adapter;

use Gaufrette\Exception;

class Sftp extends Base
{
    protected $sftp;
    protected $directory;
    protected $create;
    protected $initialized = false;

    /**
     * Constructor
     *
     * @param  \Ssh\Sftp $sftp     An Sftp instance
     * @param  string   $directory The distant directory
     * @param  boolean  $create    Whether to create the remote directory if it
     *                             does not exist
     */
    public function __construct(\Ssh\Sftp $sftp, $directory = null, $create = false)
    {
        $this->sftp      = $sftp;
        $this->directory = $directory;
        $this->create    = $create;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $this->initialize();

        $this->assertExists($key);

        $content = $this->sftp->read($this->computePath($key));

        if (false === $content) {
            throw new \RuntimeException(sprintf('Could not read the \'%s\' file.', $key));
        }

        return $content;
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

        $sourcePath = $this->computePath($sourceKey);
        $targetPath = $this->computePath($targetKey);

        $this->ensureDirectoryExists(dirname($targetPath), true);

        if(!$this->sftp->rename($sourcePath, $targetPath)) {
            throw new \RuntimeException(sprintf(
                'Could not rename the "%s" file to "%s".',
                $sourceKey,
                $targetKey
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $this->initialize();

        $path = $this->computePath($key);

        $this->ensureDirectoryExists(dirname($path), true);

        $numBytes = $this->sftp->write($path, $content);

        if (false === $numBytes) {
            throw new \RuntimeException(sprintf('Could not write the \'%s\' file.', $key));
        }

        return $numBytes;
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        $this->initialize();

        $url = $this->sftp->getUrl($this->computePath($key));

        return file_exists($url);
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $this->initialize();

        $results = $this->sftp->listDirectory($this->directory, true);

        return array_map(array($this, 'computeKey'), $results['files']);
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        $this->initialize();

        $this->assertExists($key);

        return filemtime($this->sftp->getUrl($this->computePath($key)));
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        $this->initialize();

        $this->assertExists($key);

        return md5_file($this->sftp->getUrl($this->computePath($key)));
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->initialize();

        $this->assertExists($key);

        if (!unlink($this->sftp->getUrl($this->computePath($key)))) {
            throw new \RuntimeException(sprintf('Could not delete the \'%s\' file.', $key));
        }
    }

    /**
     * Computes the path for the specified key
     *
     * @param  string $key
     *
     * @return string
     */
    protected function computePath($key)
    {
        return $this->directory . '/' . ltrim($key, '/');
    }

    /**
     * Computes the key from the specified path
     *
     * @param  string $path
     *
     * @return string
     */
    protected function computeKey($path)
    {
        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The path \'%s\' is out of the filesystem.', $path));
        }

        return ltrim(substr($path, strlen($this->directory)), '/');
    }

    /**
     * Performs the adapter's initialization
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
     * Ensures the specified directory exists
     *
     * @param  string  $directory The directory that we ensure the existence
     * @param  boolean $create    Whether to create it if it does not exist
     *
     * @throws RuntimeException if the specified directory does not exist and
     *                          could not be created
     */
    protected function ensureDirectoryExists($directory, $create = false)
    {
        $url = $this->sftp->getUrl($directory);

        if (false === @opendir($url) && (!$create || !$this->createDirectory($directory))) {
            throw new \RuntimeException(sprintf('The directory \'%s\' does not exist and could not be created.', $directory));
        }
    }

    /**
     * Creates the specified directory and its parents
     *
     * @param  string $directory The directory to create
     *
     * @return boolean TRUE on success, or FALSE on failure
     */
    protected function createDirectory($directory)
    {
        return mkdir($this->sftp->getUrl($directory), 0777, true);
    }

    /**
     * Asserts that the specified file exists
     *
     * @param  string $key
     *
     * @throws Exception\FileNotFound if the file does not exist
     */
    private function assertExists($key)
    {
        if (!$this->exists($key)) {
            throw new Exception\FileNotFound($key);
        }
    }
}
