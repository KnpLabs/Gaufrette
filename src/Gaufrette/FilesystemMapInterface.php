<?php

namespace Gaufrette;

/**
 * Associates filesystem instances to their names.
 */
interface FilesystemMapInterface
{
    /**
     * Indicates whether there is a filesystem registered for the specified
     * name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Returns the filesystem registered for the specified name.
     *
     * @param string $name
     *
     * @return FilesystemInterface
     *
     * @throw  \InvalidArgumentException when there is no filesystem registered
     *                                  for the specified name
     */
    public function get($name);
}
