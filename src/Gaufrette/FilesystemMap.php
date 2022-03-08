<?php

namespace Gaufrette;

/**
 * Associates filesystem instances to their names.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class FilesystemMap implements FilesystemMapInterface
{
    private $filesystems = [];

    /**
     * Returns an array of all the registered filesystems where the key is the
     * name and the value the filesystem.
     *
     * @return array
     */
    public function all()
    {
        return $this->filesystems;
    }

    /**
     * Register the given filesystem for the specified name.
     *
     * @param string     $name
     * @param FilesystemInterface $filesystem
     *
     * @throws \InvalidArgumentException when the specified name contains
     *                                  forbidden characters
     */
    public function set($name, FilesystemInterface $filesystem)
    {
        if (!preg_match('/^[-_a-zA-Z0-9]+$/', $name)) {
            throw new \InvalidArgumentException(sprintf(
                'The specified name "%s" is not valid.',
                $name
            ));
        }

        $this->filesystems[$name] = $filesystem;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return isset($this->filesystems[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf(
                'There is no filesystem defined having "%s" name.',
                $name
            ));
        }

        return $this->filesystems[$name];
    }

    /**
     * Removes the filesystem registered for the specified name.
     *
     * @param string $name
     */
    public function remove($name)
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot remove the "%s" filesystem as it is not defined.',
                $name
            ));
        }

        unset($this->filesystems[$name]);
    }

    /**
     * Clears all the registered filesystems.
     */
    public function clear()
    {
        $this->filesystems = [];
    }
}
