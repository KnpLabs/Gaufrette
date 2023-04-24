<?php

namespace Gaufrette;

/**
 * Associates filesystem instances to their names.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class FilesystemMap implements FilesystemMapInterface
{
    /**
     * @var array<FilesystemInterface>
     */
    private array $filesystems = [];

    /**
     * Returns an array of all the registered filesystems where the key is the
     * name and the value the filesystem.
     *
     * @return array<FilesystemInterface>
     */
    public function all(): array
    {
        return $this->filesystems;
    }

    /**
     * Register the given filesystem for the specified name.
     *
     * @throws \InvalidArgumentException when the specified name contains
     *                                   forbidden characters
     */
    public function set(string $name, FilesystemInterface $filesystem): void
    {
        if (!preg_match('/^[-_a-zA-Z0-9]+$/', $name)) {
            throw new \InvalidArgumentException(sprintf(
                'The specified name "%s" is not valid.',
                $name
            ));
        }

        $this->filesystems[$name] = $filesystem;
    }

    public function has(string $name): bool
    {
        return isset($this->filesystems[$name]);
    }

    public function get(string $name): FilesystemInterface
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
     */
    public function remove(string $name): void
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
    public function clear(): void
    {
        $this->filesystems = [];
    }
}
