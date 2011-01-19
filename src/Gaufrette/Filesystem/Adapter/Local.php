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
     * @param  string $directory The local directory in which the filesystem is
     *                           located.
     */
    public function __construct($directory)
    {
        $this->directory = realpath($directory);
    }

    /**
     * Reads the content of the file
     *
     * @param  string $key
     */
    public function read($key)
    {
        return file_get_contents($this->computePath($key));
    }

    /**
     * Writes the given content into the file
     *
     * @param  string $key
     * @param  string $content
     *
     * @return integer Number of bytes that were written into the file, or
     *                 FALSE on failure
     */
    public function write($key, $content)
    {
        $path = $this->computePath($key);

        $this->ensureDirectoryExists(dirname($path));

        return file_put_contents($this->computePath($key), $content);
    }

    /**
     * Indicates whether the file exists
     *
     * @param  string $key
     *
     * @return boolean
     */
    public function exists($key)
    {
        return is_file($this->computePath($key));
    }

    /**
     * Don't forget the "/" if you want to list a specific directory
     *
     * @param  string $pattern
     *
     * @return array
     */
    public function keys($pattern)
    {
        $pattern = ltrim(str_replace('\\', '/', $pattern), '/');

        $pos = strrpos($pattern, '/');
        if (false === $post) {
            return $this->listDirectory($this->computePath(null), $pattern);
        } elseif (strlen($pattern) === $pos + 1) {
            return $this->listDirectory($this->computePath(dirname($pattern)), null);
        } else {
            return $this->listDirectory($this->computePath(dirname($pattern)), basename($pattern));
        }
    }

    /**
     * Lists files from the specified directory and matching the given pattern
     *
     * @param  string $directory
     * @param  string $pattern
     */
    protected function listDirectory($directory, $pattern)
    {
        $iterator = new \RecursiveDirectoryIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        if (!empty($pattern)) {
            $iterator = new \RegexIterator(
                $iterator,
                sprintf('/^%s.+/', preg_quote($pattern)),
                RecursiveRegexIterator::GET_MATCH
            );
        }

        $keys = array();
        foreach ($iterator as $filename => $current) {
            if ($current->isFile()) {
                $keys[] = $this->computeKey($directory . '/' . $filename);
            }
        }

        return $keys;
    }

    /**
     * Computes the path from the specified key
     *
     * @param  string $key
     *
     * @return string
     */
    protected function computePath($key)
    {
        $path = realpath($this->directory . '/' . $key);

        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The file \'%s\' is out of the filesystem.', $key));
        }

        return $path;
    }

    /**
     * Computes the key from the specified path
     *
     * @param  string $path
     *
     * return string
     */
    protected function computeKey($path)
    {
        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The path \'%s\' is out of the filesystem.', $path));
        }

        return substr($path, strlen($this->directory));
    }

    /**
     * Ensures the specified directory exists, creates it if it does not
     *
     * @param  string $directory
     */
    protected function ensureDirectoryExists($directory)
    {
        if (!is_dir($directory)) {
            $this->createDirectory($directory);
        }
    }

    /**
     * Creates the specified directory and its parents
     *
     * @param  string $directory
     */
    protected function createDirectory($directory)
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
