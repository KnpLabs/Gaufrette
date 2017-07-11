<?php

namespace Gaufrette;

interface FilesystemInterface
{
    /**
     * Indicates whether the file matching the specified key exists.
     *
     * @param string $key
     *
     * @return bool TRUE if the file exists, FALSE otherwise
     *
     * @throws \InvalidArgumentException If $key is invalid
     * @throws Exception\StorageFailure  When the underlying storage fails asserting file exists
     */
    public function has($key);

    /**
     * Renames a file.
     *
     * File::rename should be preferred or you may face bad filesystem consistency.
     *
     * @param string $sourceKey
     * @param string $targetKey
     *
     * @return bool TRUE if the rename was successful
     *
     * @throws Exception\FileNotFound    when sourceKey does not exist
     * @throws Exception\UnexpectedFile  when targetKey exists
     * @throws Exception\StorageFailure  When the underlying storage fails renaming the file
     * @throws \InvalidArgumentException If $sourceKey or $targetKey are invalid
     *
     * @see File::rename()
     */
    public function rename($sourceKey, $targetKey);


    /**
     * Returns the file matching the specified key.
     *
     * @param string $key    Key of the file
     * @param bool   $create Whether to create the file if it does not exist
     *
     * @throws Exception\FileNotFound
     * @throws \InvalidArgumentException If $key is invalid
     *
     * @return File
     */
    public function get($key, $create = false);

    /**
     * Writes the given content into the file.
     *
     * @param string $key       Key of the file
     * @param string $content   Content to write in the file
     * @param bool   $overwrite Whether to overwrite the file if exists
     *
     * @throws Exception\FileAlreadyExists When file already exists and overwrite is false
     * @throws \InvalidArgumentException   If $key is invalid
     * @throws Exception\StorageFailure    When the underlying storage fails writing the content
     */
    public function write($key, $content, $overwrite = false);

    /**
     * Reads the content from the file.
     *
     * @param string $key Key of the file
     *
     * @throws Exception\FileNotFound    when file does not exist
     * @throws \InvalidArgumentException If $key is invalid
     * @throws Exception\StorageFailure  When the underlying storage fails reading the file
     *
     * @return string
     */
    public function read($key);

    /**
     * Deletes the file matching the specified key.
     *
     * @param string $key
     *
     * @throws \RuntimeException         when cannot read file
     * @throws \InvalidArgumentException If $key is invalid
     * @throws Exception\StorageFailure  When the underlying storage fails deleting the file
     */
    public function delete($key);

    /**
     * Returns an array of all keys.
     *
     * @return array
     *
     * @throws Exception\StorageFailure  When the underlying storage fails listing objects
     */
    public function keys();

    /**
     * Lists keys beginning with given prefix
     * (no wildcard / regex matching).
     *
     * if adapter implements ListKeysAware interface, adapter's implementation will be used,
     * in not, ALL keys will be requested and iterated through.
     *
     * @param string $prefix
     *
     * @return array
     *
     * @throws Exception\StorageFailure  When the underlying storage fails listing objects
     */
    public function listKeys($prefix = '');

    /**
     * Returns the last modified time of the specified file.
     *
     * @param string $key
     *
     * @return int An UNIX like timestamp
     *
     * @throws \InvalidArgumentException If $key is invalid
     * @throws Exception\StorageFailure  When the underlying storage fails retrieving file mtime
     */
    public function mtime($key);

    /**
     * Returns the checksum of the specified file's content.
     *
     * @param string $key
     *
     * @return string A MD5 hash
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function checksum($key);

    /**
     * Returns the size of the specified file's content.
     *
     * @param string $key
     *
     * @return int File size in Bytes
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function size($key);

    /**
     * Gets a new stream instance of the specified file.
     *
     * @param $key
     *
     * @return Stream|Stream\InMemoryBuffer
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function createStream($key);

    /**
     * Creates a new file in a filesystem.
     *
     * @param $key
     *
     * @return File
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function createFile($key);

    /**
     * Get the mime type of the provided key.
     *
     * @param string $key
     *
     * @return string
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function mimeType($key);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isDirectory($key);
}
