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
class Local implements Adapter, StreamFactory, ChecksumCalculator, SizeCalculator, MimeTypeProvider
{
    protected string $directory;
    private bool $create;
    private int $mode;
    /**
     * @param string $directory Directory where the filesystem is located
     * @param bool   $create    Whether to create the directory if it does not
     *                          exist (default FALSE)
     * @param int    $mode      Mode for mkdir
     *
     * @throws \RuntimeException if the specified directory does not exist and
     *                          could not be created
     */
    public function __construct(
        string $directory, 
        bool $create = false, 
        int $mode = 0777
    ){
        $this->directory = Util\Path::normalize($directory);

        if (is_link($this->directory)) {
            $this->directory = realpath($this->directory);
        }

        $this->create = $create;
        $this->mode = $mode;
    }

    /**
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function read(string $key): string|bool
    {
        if ($this->isDirectory($key)) {
            return false;
        }

        return file_get_contents($this->computePath($key));
    }

    /**
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function write(string $key, mixed $content): int|bool
    {
        $path = $this->computePath($key);
        $this->ensureDirectoryExists(\Gaufrette\Util\Path::dirname($path), true);

        return file_put_contents($path, $content);
    }

    /**
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        $targetPath = $this->computePath($targetKey);
        $this->ensureDirectoryExists(\Gaufrette\Util\Path::dirname($targetPath), true);

        return rename($this->computePath($sourceKey), $targetPath);
    }

    public function exists(string $key): bool
    {
        return is_file($this->computePath($key));
    }

    /**
     * @return array<int, string> 
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function keys(): array
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
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function mtime(string $key): int|bool
    {
        return filemtime($this->computePath($key));
    }

    /**
     * Can also delete a directory recursively when the given $key matches a
     * directory.
     */
    public function delete(string $key): bool
    {
        if ($this->isDirectory($key)) {
            return $this->deleteDirectory($this->computePath($key));
        } elseif ($this->exists($key)) {
            return unlink($this->computePath($key));
        }

        return false;
    }

    /**
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function isDirectory(string $key): bool
    {
        return is_dir($this->computePath($key));
    }

    /**
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function createStream(string $key): Stream\Local
    {
        return new Stream\Local($this->computePath($key), $this->mode);
    }

    /**
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function checksum(string $key): string|bool
    {
        return Util\Checksum::fromFile($this->computePath($key));
    }

    /**
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function size(string $key): int
    {
        return Util\Size::fromFile($this->computePath($key));
    }

    /**
     * @throws \OutOfBoundsException     If the computed path is out of the directory
     * @throws \InvalidArgumentException if the directory already exists
     * @throws \RuntimeException         if the directory could not be created
     */
    public function mimeType(string $key): string
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
    public function computeKey(string $path): string
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
    protected function computePath(string $key): string
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        return $this->normalizePath($this->directory . '/' . $key);
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
    protected function normalizePath(string $path): string
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
    protected function ensureDirectoryExists(string $directory, bool $create = false): void
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
    protected function createDirectory(string $directory): void
    {
        if (!@mkdir($directory, $this->mode, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('The directory \'%s\' could not be created.', $directory));
        }
    }

    /**
     * @param string The directory's path to delete
     *
     * @throws \InvalidArgumentException When attempting to delete the root
     * directory of this adapter.
     *
     * @return bool Whether the operation succeeded or not
     */
    private function deleteDirectory(string $directory): bool
    {
        if ($this->directory === $directory) {
            throw new \InvalidArgumentException(
                sprintf('Impossible to delete the root directory of this Local adapter ("%s").', $directory)
            );
        }

        $status = true;

        if (file_exists($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $directory,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    $status = $status && rmdir(strval($item));
                } else {
                    $status = $status && unlink(strval($item));
                }
            }

            $status = $status && rmdir($directory);
        }

        return $status;
    }
}
