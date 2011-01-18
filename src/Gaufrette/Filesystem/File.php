<?php

namespace Gaufrette\Filesystem;

/**
 * Points to a file in a filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class File
{
    protected $key;
    protected $filesystem;

    /**
     * Constructor
     *
     * @param  string     $key
     * @param  Filesystem $filesystem An optional filesystem
     */
    public function __construct($key, $filesystem = null)
    {
        $this->key = $key;
        $this->filesystem = $filesystem;
    }

    /**
     * Returns the key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Returns the filesystem
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return $this->filesystem;
    }

    /**
     * Sets the filesystem
     *
     * @param  Filesystem $filesystem
     */
    public function setFilesystem(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Returns the content
     *
     * @return string
     */
    public function getContent()
    {
        if (null === $this->filesystem) {
            throw new \LogicException('The filesystem is not defined.');
        }

        return $this->filesystem->read($this->key);
    }

    /**
     * Sets the content
     *
     * @param  string $content
     *
     * @return integer The number of bytes that were written into the file, or
     *                 FALSE on failure
     */
    public function setContent($content)
    {
        if (null === $this->filesystem) {
            throw new \LogicException('The filesystem is not defined.');
        }

        return $this->filesystem->write($this->key, $content, true);
    }
}
