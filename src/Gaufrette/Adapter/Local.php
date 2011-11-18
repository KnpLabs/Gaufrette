<?php

namespace Gaufrette\Adapter;

use Gaufrette\Checksum;
use Gaufrette\Path;
use Gaufrette\Filesystem;
use Gaufrette\FileStream;

/**
 * Adapter for the local filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Local extends Base
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

        if (is_link($this->directory)) {
            $this->directory = readlink($this->directory);
        }

        $this->ensureDirectoryExists($this->directory, $create);
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $content = file_get_contents($this->computePath($key));

        if (false === $content) {
            throw new \RuntimeException(sprintf('Could not read the \'%s\' file.', $key));
        }

        return $content;
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $path = $this->computePath($key);

        $this->ensureDirectoryExists(dirname($path), true);

        $numBytes = file_put_contents($this->computePath($key), $content);

        if (false === $numBytes) {
            throw new \RuntimeException(sprintf('Could not write the \'%s\' file.', $key));
        }

        return $numBytes;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($key, $new)
    {
        if (!rename($this->computePath($key), $this->computePath($new))) {
            throw new \RuntimeException(sprintf('Could not rename the \'%s\' file to \'%s\'.', $key, $new));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return is_file($this->computePath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->directory,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
            )
        );

        $files = iterator_to_array($iterator);

        $self = $this;
        return array_values(
            array_map(
                function($file) use ($self) {
                    return $self->computeKey(strval($file));
                },
                $files
            )
        );
    }

    /**
     * {@inheritDoc}
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
        return Checksum::fromFile($this->computePath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        if (!unlink($this->computePath($key))) {
            throw new \RuntimeException(sprintf('Could not remove the \'%s\' file.', $key));
        }
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
     * Normalizes the given path
     *
     * @param  string $path
     *
     * @return string
     */
    public function normalizePath($path)
    {
        return Path::normalize($path);
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

    /**
     * {@inheritDoc}
     */
    public function supportsMetadata()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function createFileStream($key, Filesystem $filesystem)
    {
        return new FileStream\Local($this->computePath($key));
    }
}
