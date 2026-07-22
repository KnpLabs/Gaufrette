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
     * @return string|false Returns FALSE in content is not readable
     */
    public function read(string $key): string|bool;

    /**
     * Writes the given content into the file.
     *
     * @return int|bool The number of bytes that were written into the file
     */
    public function write(string $key, mixed $content): int|bool;

    /**
     * Indicates whether the file exists.
     */
    public function exists(string $key): bool;

    /**
     * Returns an array of all keys (files and directories).
     *
     * @return array<int, string>
     */
    public function keys(): array;

    /**
     * Returns the last modified time.
     *
     * @return int|bool An UNIX like timestamp or false
     */
    public function mtime(string $key): int|bool;

    /**
     * Deletes the file.
     */
    public function delete(string $key): bool;

    /**
     * Renames a file.
     */
    public function rename(string $sourceKey, string $targetKey): bool;

    /**
     * Check if key is directory.
     */
    public function isDirectory(string $key): bool;
}
