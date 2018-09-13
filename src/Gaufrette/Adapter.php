<?php

namespace Gaufrette;

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
     * @return string|bool if cannot read content
     */
    public function read($key);

    /**
     * Writes the given content into the file.
     *
     * @param string $key
     * @param string $content
     *
     * @return int|bool The number of bytes that were written into the file
     */
    public function write($key, $content);

    /**
     * Indicates whether the file exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key);

    /**
     * Returns an array of all keys (files and directories).
     *
     * @return array
     */
    public function keys();

    /**
     * Returns the last modified time.
     *
     * @param string $key
     *
     * @return int|bool An UNIX like timestamp or false
     */
    public function mtime($key);

    /**
     * Deletes the file.
     *
     * @param string $key
     *
     * @return bool
     */
    public function delete($key);

    /**
     * Renames a file.
     *
     * @param string $sourceKey
     * @param string $targetKey
     *
     * @return bool
     */
    public function rename($sourceKey, $targetKey);

    /**
     * Check if key is directory.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isDirectory($key);
}
