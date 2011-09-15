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
    public function read($key);

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
    public function write($key, $content, $metadata=null);

    /**
     * Indicates whether the file exists
     *
     * @param  string $key
     *
     * @return boolean
     */
    public function exists($key);

    /**
     * Returns an array of all keys matching the specified pattern
     *
     * @return array
     */
    public function keys();

    /**
     * Returns the last modified time
     *
     * @param  string $key
     *
     * @return integer An UNIX like timestamp
     */
    public function mtime($key);

    /**
     * Returns the checksum of the file
     *
     * @param  string $key
     *
     * @return string
     */
    public function checksum($key);

    /**
     * Deletes the file
     *
     * @param  string $key
     *
     * @throws RuntimeException on failure
     */
    public function delete($key);

    /**
     * Renames a file
     *
     * @param string $key
     * @param string $new
     *
     * @throws RuntimeException on failure
     */
    public function rename($key, $new);
    
    
    /**
     * If the adapter can allow inserting metadata
     * 
     * @param bool true if supports metadata false if not
     */
    public function supportsMetadata();
    
}
