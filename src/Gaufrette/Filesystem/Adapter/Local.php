<?php

namespace Gaufrette\Filesystem\Adapter;

use Gaufrette\Filesystem\Adapter;

/**
 * Adapter for the local filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Local implements Adapter
{
    protected $directory;

    /**
     * Constructor
     *
     * @param  string  $directory Directory where the filesystem is located
     * @param  boolean $create    Whether to create the directory if it does not
     *                            exist (default FALSE)
     *
     * @throws RuntimeException if the specified directory does not exist and
     *                          could not be created
     */
    public function __construct($directory, $create = false)
    {
        $this->directory = $this->normalizePath($directory);
        $this->ensureDirectoryExists($this->directory, $create);
    }

    /**
     * {@InheritDoc}
     */
    public function read($key)
    {
        return file_get_contents($this->computePath($key));
    }

    /**
     * {@InheritDoc}
     */
    public function write($key, $content)
    {
        $path = $this->computePath($key);

        $this->ensureDirectoryExists(dirname($path), true);

        return file_put_contents($this->computePath($key), $content);
    }

    /**
     * {@InheritDoc}
     */
    public function exists($key)
    {
        return is_file($this->computePath($key));
    }

    /**
     * {@InheritDoc}
     */
    public function keys($pattern)
    {
        $pattern = ltrim(str_replace('\\', '/', $pattern), '/');

        $pos = strrpos($pattern, '/');
        if (false === $pos) {
            return $this->listDirectory($this->computePath(null), $pattern);
        } elseif (strlen($pattern) === $pos + 1) {
            return $this->listDirectory($this->computePath($pattern), null);
        } else {
            return $this->listDirectory($this->computePath(dirname($pattern)), basename($pattern));
        }
    }

    /**
     * {@InheritDoc}
     */
    public function mtime($key)
    {
        return filemtime($this->computePath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        return md5_file($this->computePath($key));
    }

    /**
     * {@InheritDoc}
     */
    public function delete($key)
    {
        return unlink($this->computePath($key));
    }

    /**
     * Recursively lists files from the specified directory. If a pattern is
     * specified, it only returns files matching it.
     *
     * @param  string $directory The path of the directory to list files from
     * @param  string $pattern   The pattern that files must match to be
     *                           returned
     *
     * @return array An array of file keys
     */
    public function listDirectory($directory, $pattern = null)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS)
        );

        if (!empty($pattern)) {
            $iterator = new \RegexIterator(
                $iterator,
                sprintf('/^%s/', preg_quote($directory . '/' . $pattern, '/')),
                \RecursiveRegexIterator::MATCH
            );
        }

        $keys = array();
        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $keys[] = $this->computeKey(strval($item));
            }
        }

        return $keys;
    }

    /**
     * Computes the path from the specified key
     *
     * @param  string $key The key which for to compute the path
     *
     * @return string A path
     *
     * @throws OutOfBoundsException If the computed path is out of the
     *                              directory
     */
    public function computePath($key)
    {
        $path = $this->normalizePath($this->directory . '/' . $key);

        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The file \'%s\' is out of the filesystem.', $key));
        }

        return $path;
    }

    /**
     * Normalizes the given path. It replaces backslashes by slashes, resolves
     * dots and removes double slashes
     *
     * @param  string $path The path to normalize
     *
     * @return string A normalized path
     *
     * @throws OutOfBoundsException If the given path is out of the directory
     */
    public function normalizePath($path)
    {
        // normalize directory separator and remove double slashes
        $path = str_replace(array('/', '\\'), '/', $path);
        $absolute = $path[0] == '/';
        $parts = array_filter(explode('/', $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        return ($absolute ? '/' : '') . implode('/', $absolutes);
    }

    /**
     * Computes the key from the specified path
     *
     * @param  string $path
     *
     * return string
     */
    public function computeKey($path)
    {
        $path = $this->normalizePath($path);
        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The path \'%s\' is out of the filesystem.', $path));
        }

        return ltrim(substr($path, strlen($this->directory)), '/');
    }

    /**
     * Ensures the specified directory exists, creates it if it does not
     *
     * @param  string  $directory Path of the directory to test
     * @param  boolean $create    Whether to create the directory if it does
     *                            not exist
     *
     * @throws RuntimeException if the directory does not exists and could not
     *                          be created
     */
    public function ensureDirectoryExists($directory, $create = false)
    {
        if (!is_dir($directory)) {
            if (!$create) {
                throw new \RuntimeException(sprintf('The directory \'%s\' does not exist.', $directory));
            }

            $this->createDirectory($directory);
        }
    }

    /**
     * Creates the specified directory and its parents
     *
     * @param  string $directory Path of the directory to create
     *
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    public function createDirectory($directory)
    {
        if (is_dir($directory)) {
            throw new \InvalidArgumentException(sprintf('The directory \'%s\' already exists.', $directory));
        }

        $umask = umask(0);
        $created = mkdir($directory, 0777, true);
        umask($umask);

        if (!$created) {
            throw new \RuntimeException(sprintf('The directory \'%s\' could not be created.', $directory));
        }
    }
}
