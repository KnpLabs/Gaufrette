<?php

namespace Gaufrette\Filesystem;

/**
 * A filesystem is used to store and retrieve files
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Filesystem
{
    protected $adapter;

    /**
     * Constructor
     *
     * @param  Adapter $adapter A configured Adapter instance
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Indicates whether the file matching the specified key exists
     *
     * @param  string $key
     *
     * @return boolean TRUE if the file exists, FALSE otherwise
     */
    public function has($key)
    {
        return $this->adapter->exists($key);
    }

    /**
     * Renames a file
     *
     * @param string $key
     * @param string $new
     *
     * @return boolean TRUE if the rename was successful, FALSE otherwise
     */
    public function rename($key, $new)
    {
        return $this->adapter->rename($key, $new);
    }

    /**
     * Returns the file matching the specified key
     *
     * @param  string  $key    Key of the file
     * @param  boolean $create Whether to create the file if it does not exist
     *
     * @return File
     */
    public function get($key, $create = false)
    {
        if (!$create && !$this->has($key)) {
            throw new \InvalidArgumentException(sprintf('The file %s does not exist.', $key));
        }

        return $this->createFileInstance($key);
    }

    /**
     * Writes the given content into the file
     *
     * @param  string  $key       Key of the file
     * @param  string  $content   Content to write in the file
     * @param  boolean $overwrite Whether to overwrite the file if exists
     *
     * @return integer The number of bytes that were written into the file
     */
    public function write($key, $content, $overwrite = false)
    {
        if (!$overwrite && $this->has($key)) {
            throw new \InvalidArgumentException(sprintf('The file %s already exists and can not be overwritten.', $key));
        }

        return $this->adapter->write($key, $content);
    }

    /**
     * Reads the content from the file
     *
     * @param  string $key Key of the file
     *
     * @return string
     */
    public function read($key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(sprintf('The file %s does not exist.', $key));
        }

        return $this->adapter->read($key);
    }

    /**
     * Deletes the file matching the specified key
     *
     * @param  string $key
     *
     * @return boolean
     */
    public function delete($key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(sprintf('The file %s does not exist.', $key));
        }

        return $this->adapter->delete($key);
    }

    /**
     * Returns an array of all keys matching the specified pattern
     *
     * @return array
     */
    public function keys()
    {
        return $this->adapter->keys();
    }

    /**
     * Returns the last modified time of the specified file
     *
     * @param  string $key
     *
     * @return integer An UNIX like timestamp
     */
    public function mtime($key)
    {
        return $this->adapter->mtime($key);
    }

    /**
     * Returns the checksum of the specified file's content
     *
     * @param  string $key
     *
     * @return integer An UNIX like timestamp
     */
    public function checksum($key)
    {
        return $this->adapter->checksum($key);
    }

    /**
     * Creates a new File instance and returns it
     *
     * @param  string $key
     *
     * @return $file
     */
    protected function createFileInstance($key)
    {
        return new File($key, $this);
    }
}
