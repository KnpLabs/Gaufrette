<?php

namespace Gaufrette\Filesystem;

/**
 * Interface for the filesystem adapters
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
interface Adapter
{
    /**
     * Reads the content of the file
     *
     * @param  string $key
     *
     * @return string
     */
    function read($key);

    /**
     * Writes the given content into the file
     *
     * @param  string $key
     * @param  string $content
     *
     * @return integer The number of bytes that were written into the file
     *
     * @throws RuntimeException on failure
     */
    function write($key, $content);

    /**
     * Indicates whether the file exists
     *
     * @param  string $key
     *
     * @return boolean
     */
    function exists($key);

    /**
     * Returns an array of all keys matching the specified pattern
     *
     * @return array
     */
    function keys();

    /**
     * Returns the last modified time
     *
     * @param  string $key
     *
     * @return integer An UNIX like timestamp
     */
    function mtime($key);

    /**
     * Returns the checksum of the file
     *
     * @param  string $key
     *
     * @return string
     */
    function checksum($key);

    /**
     * Deletes the file
     *
     * @param  string $key
     *
     * @throws RuntimeException on failure
     */
    function delete($key);

    /**
     * Renames a file
     *
     * @param string $key
     * @param string $new
     *
     * @throws RuntimeException on failure
     */
    function rename($key, $new);
}
