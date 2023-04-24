<?php

namespace Gaufrette;

interface FilesystemInterface
{
    /**
     * Returns the adapter.
     */
    public function getAdapter(): Adapter;

    /**
     * Indicates whether the file matching the specified key exists.
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function has(string $key): bool;

    /**
     * Renames a file.
     *
     * File::rename should be preferred or you may face bad filesystem consistency.
     *
     * @return bool TRUE if the rename was successful
     *
     * @throws Exception\FileNotFound    when sourceKey does not exist
     * @throws Exception\UnexpectedFile  when targetKey exists
     * @throws \RuntimeException         when cannot rename
     * @throws \InvalidArgumentException If $sourceKey or $targetKey are invalid
     *
     * @see File::rename()
     */
    public function rename(string $sourceKey, string $targetKey): bool;

    /**
     * Returns the file matching the specified key.
     *
     * @param string $key    Key of the file
     * @param bool   $create Whether to create the file if it does not exist
     *
     * @throws Exception\FileNotFound
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function get(string $key, bool $create = false): File;

    /**
     * Writes the given content into the file.
     *
     * @param string $key       Key of the file
     * @param string $content   Content to write in the file
     * @param bool   $overwrite Whether to overwrite the file if exists
     *
     * @throws Exception\FileAlreadyExists When file already exists and overwrite is false
     * @throws \RuntimeException           When for any reason content could not be written
     * @throws \InvalidArgumentException   If $key is invalid
     *
     * @return int The number of bytes that were written into the file
     */
    public function write(string $key, string $content, bool $overwrite = false): int;

    /**
     * Reads the content from the file.
     *
     * @param string $key Key of the file
     *
     * @throws Exception\FileNotFound    when file does not exist
     * @throws \RuntimeException         when cannot read file
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function read(string $key): string;

    /**
     * Deletes the file matching the specified key.
     *
     * @throws \RuntimeException         when cannot read file
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function delete(string $key): bool;

    /**
     * Returns an array of all keys.
     *
     * @return array<string>
     */
    public function keys(): array;

    /**
     * Lists keys beginning with given prefix
     * (no wildcard / regex matching).
     *
     * if adapter implements ListKeysAware interface, adapter's implementation will be used,
     * in not, ALL keys will be requested and iterated through.
     *
     * @return array{
     *     keys: array<string>,
     *     dirs: array<string>
     * }
     */
    public function listKeys(string $prefix = '');

    /**
     * Returns the last modified time of the specified file.
     *
     * @return int An UNIX like timestamp
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function mtime(string $key): int;

    /**
     * Returns the checksum of the specified file's content.
     *
     * @return string A MD5 hash
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function checksum(string $key): string;

    /**
     * Returns the size of the specified file's content.
     *
     * @return int File size in Bytes
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function size(string $key): int;

    /**
     * Gets a new stream instance of the specified file.
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function createStream(string $key): Stream;

    /**
     * Creates a new file in a filesystem.
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function createFile(string $key): File;

    /**
     * Get the mime type of the provided key.
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function mimeType(string $key): string;

    public function isDirectory(string $key): bool;
}
