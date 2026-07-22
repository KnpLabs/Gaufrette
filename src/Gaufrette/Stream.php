<?php

namespace Gaufrette;

/**
 * Interface for the file streams.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
interface Stream
{
    /**
     * Opens the stream in the specified mode.
     *
     * @param StreamMode $mode
     *
     * @return bool TRUE on success or FALSE on failure
     */
    public function open(StreamMode $mode): bool;

    /**
     * Reads the specified number of bytes from the current position.
     *
     * If the current position is the end-of-file, you must return an empty
     * string.
     *
     * @param int $count The number of bytes
     */
    public function read(int $count): string|bool;

    /**
     * Writes the specified data.
     *
     * Don't forget to update the current position of the stream by number of
     * bytes that were successfully written.
     *
     * @return int The number of bytes that were successfully written
     */
    public function write(string $data): int;

    /**
     * Closes the stream.
     *
     * It must free all the resources. If there is any data to flush, you
     * should do so
     */
    public function close(): void;

    /**
     * Flushes the output.
     *
     * If you have cached data that is not yet stored into the underlying
     * storage, you should do so now
     *
     * @return bool TRUE on success or FALSE on failure
     */
    public function flush(): bool;

    /**
     * Seeks to the specified offset.
     */
    /**
     * @param SEEK_SET|SEEK_CUR|SEEK_END $whence
     */
    public function seek(int $offset, int $whence = SEEK_SET): bool;

    /**
     * Returns the current position.
     */
    public function tell(): int;

    /**
     * Indicates whether the current position is the end-of-file.
     */
    public function eof(): bool;

    /**
     * Gathers statistics of the stream.
     *
     * @return array<string, mixed>|false
     */
    public function stat(): array|bool;

    /**
     * Retrieve the underlying resource.
     *
     * @param STREAM_CAST_FOR_SELECT|STREAM_CAST_AS_STREAM $castAs
     * @return resource|false using resource or false
     */
    public function cast(int $castAs);

    /**
     * Delete a file.
     *
     * @return bool TRUE on success FALSE otherwise
     */
    public function unlink(): bool;
}
