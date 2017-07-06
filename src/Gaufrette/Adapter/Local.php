<?php

namespace Gaufrette\Adapter;

use Gaufrette\Util;
use Gaufrette\Adapter;
use Gaufrette\Stream;


/**
 * Adapter for the local filesystem.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class Local implements Adapter,
    StreamFactory,
    ChecksumCalculator,
    SizeCalculator,
    MimeTypeProvider
{
    protected $directory;
    private $create;
    private $mode;

    /**
     * @param string $directory Directory where the filesystem is located
     * @param bool   $create    Whether to create the directory if it does not
     *                          exist (default FALSE)
     * @param int    $mode      Mode for mkdir
     *
     * @throws \RuntimeException if the specified directory does not exist and
     *                          could not be created
     */
    public function __construct($directory, $create = false, $mode = 0777)
    {
        $this->directory = Util\Path::normalize($directory);

        if (is_link($this->directory)) {
            $this->directory = realpath($this->directory);
        }

        $this->create = $create;
        $this->mode = $mode;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function read($key)
    {
        return file_get_contents($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function write($key, $content)
    {
        $path = $this->computePath($key);
        $this->ensureDirectoryExists(\Gaufrette\Util\Path::dirname($path), true);

        return file_put_contents($path, $content);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function rename($sourceKey, $targetKey)
    {
        $targetPath = $this->computePath($targetKey);
        $this->ensureDirectoryExists(\Gaufrette\Util\Path::dirname($targetPath), true);

        return rename($this->computePath($sourceKey), $targetPath);
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        return is_file($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function keys()
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        try {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->directory,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
        } catch (\Exception $e) {
            $files = new \EmptyIterator();
        }

        $keys = [];
        foreach ($files as $file) {
            $keys[] = $this->computeKey($file);
        }
        sort($keys);

        return $keys;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function mtime($key)
    {
        return filemtime($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function delete($key)
    {
        if ($this->isDirectory($key)) {
            return rmdir($this->computePath($key));
        }

        return unlink($this->computePath($key));
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function isDirectory($key)
    {
        return is_dir($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function createStream($key)
    {
        return new Stream\Local($this->computePath($key), $this->mode);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function checksum($key)
    {
        return Util\Checksum::fromFile($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function size($key)
    {
        return Util\Size::fromFile($this->computePath($key));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function mimeType($key)
    {
        $fileInfo = new \finfo(FILEINFO_MIME_TYPE);

        return $fileInfo->file($this->computePath($key));
    }

    /**
     * Computes the key from the specified path.
     *
     * @param $path
     * @return string
     *
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function computeKey($path)
    {
        $path = $this->normalizePath($path);

        return ltrim(substr($path, strlen($this->directory)), '/');
    }

    /**
     * Computes the path from the specified key.
     *
     * @param string $key The key which for to compute the path
     *
     * @return string A path
     *
     * @throws \InvalidArgumentException If the directory already exists
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \RuntimeException         If directory does not exists and cannot be created
     */
    protected function computePath($key)
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        return $this->normalizePath($this->directory.'/'.$key);
    }

    /**
     * Normalizes the given path.
     *
     * @param string $path
     *
     * @return string
     * @throws \OutOfBoundsException If the computed path is out of the
     *                              directory
     */
    protected function normalizePath($path)
    {
        $path = Util\Path::normalize($path);

        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The path "%s" is out of the filesystem.', $path));
        }

        return $path;
    }

    /**
     * Ensures the specified directory exists, creates it if it does not.
     *
     * @param string $directory Path of the directory to test
     * @param bool   $create    Whether to create the directory if it does
     *                          not exist
     *
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException if the directory does not exists and could not
     *                          be created
     */
    protected function ensureDirectoryExists($directory, $create = false)
    {
        if (!is_dir($directory)) {
            if (!$create) {
                throw new \RuntimeException(sprintf('The directory "%s" does not exist.', $directory));
            }

            $this->createDirectory($directory);
        }
    }

    /**
     * Creates the specified directory and its parents.
     *
     * @param string $directory Path of the directory to create
     *
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    protected function createDirectory($directory)
    {
        if (!@mkdir($directory, $this->mode, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('The directory \'%s\' could not be created.', $directory));
        }
    }
}
