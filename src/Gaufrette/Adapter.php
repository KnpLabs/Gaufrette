<?php

namespace Gaufrette;

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
     * @param  array $metadata or null if none (optional)
     *
     * @return integer The number of bytes that were written into the file
     *
     * @throws RuntimeException on failure
     */
    function write($key, $content, array $metadata = null);

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

    /**
     * If the adapter can allow inserting metadata
     *
     * @return bool true if supports metadata, false if not
     */
    function supportsMetadata();

    /**
     * Creates an new file instance for the specified file
     *
     * @param  string     $key
     * @param  Filesystem $filesystem
     *
     * @return File
     */
    function createFile($key, Filesystem $filesystem);

    /**
     * Creates a new file stream instance of the specified file
     *
     * @param  string     $key
     * @param  Filesystem $filesystem
     *
     * @return FileStream
     */
    function createFileStream($key, Filesystem $filesystem);
}
