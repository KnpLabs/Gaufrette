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
    public function open(StreamMode $mode);

    /**
     * Reads the specified number of bytes from the current position.
     *
     * If the current position is the end-of-file, you must return an empty
     * string.
     *
     * @param int $count The number of bytes
     *
     * @return string
     */
    public function read($count);

    /**
     * Writes the specified data.
     *
     * Don't forget to update the current position of the stream by number of
     * bytes that were successfully written.
     *
     * @param string $data
     *
     * @return int The number of bytes that were successfully written
     */
    public function write($data);

    /**
     * Closes the stream.
     *
     * It must free all the resources. If there is any data to flush, you
     * should do so
     */
    public function close();

    /**
     * Flushes the output.
     *
     * If you have cached data that is not yet stored into the underlying
     * storage, you should do so now
     *
     * @return bool TRUE on success or FALSE on failure
     */
    public function flush();

    /**
     * Seeks to the specified offset.
     *
     * @param int $offset
     * @param int $whence
     *
     * @return bool
     */
    public function seek($offset, $whence = SEEK_SET);

    /**
     * Returns the current position.
     *
     * @return int
     */
    public function tell();

    /**
     * Indicates whether the current position is the end-of-file.
     *
     * @return bool
     */
    public function eof();

    /**
     * Gathers statistics of the stream.
     *
     * @return array
     */
    public function stat();

    /**
     * Retrieve the underlying resource.
     *
     * @param int $castAs
     *
     * @return mixed using resource or false
     */
    public function cast($castAs);

    /**
     * Delete a file.
     *
     * @return bool TRUE on success FALSE otherwise
     */
    public function unlink();
}
