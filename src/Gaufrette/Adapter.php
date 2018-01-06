<?php

namespace Gaufrette;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\InvalidKey;
use Gaufrette\Exception\StorageFailure;

/**
 * Interface for the filesystem adapters.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
interface Adapter
{
    /**
     * Reads the content of the file.
     *
     * @param string $key
     *
     * @return string
     *
     * @throws FileNotFound
     * @throws InvalidKey     If $key is malformed
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function read($key);

    /**
     * Writes the given content into the file.
     *
     * @param string $key
     * @param string $content
     *
     * @throws InvalidKey     If $key is malformed
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function write($key, $content);

    /**
     * Indicates whether the file exists.
     *
     * @param string $key
     *
     * @return bool
     *
     * @throws InvalidKey     If $key is malformed
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function exists($key);

    /**
     * Returns an array of all keys (files and directories).
     *
     * @return array
     *
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function keys();

    /**
     * Returns the last modified time.
     *
     * @param string $key
     *
     * @return int An UNIX like timestamp
     *
     * @throws FileNotFound
     * @throws InvalidKey     If $key is malforme
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function mtime($key);

    /**
     * Deletes the file.
     *
     * @param string $key
     *
     * @throws FileNotFound
     * @throws InvalidKey     If $key is malformed
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function delete($key);

    /**
     * Renames a file.
     *
     * @param string $sourceKey
     * @param string $targetKey
     *
     * @throws FileNotFound
     * @throws InvalidKey     If $sourceKey and/or $targetKey are malformed
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function rename($sourceKey, $targetKey);

    /**
     * Check if key is directory.
     *
     * @param string $key
     *
     * @return bool
     *
     * @throws InvalidKey     If $key is malformed
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     *
     * @TODO: remove this method
     */
    public function isDirectory($key);
}
