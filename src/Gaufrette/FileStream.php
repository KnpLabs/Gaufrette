<?php

namespace Gaufrette;

/**
 * Interface for the file streams
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
interface FileStream
{
    /**
     * Opens the stream in the specified mode
     *
     * @param  StreamMode $mode
     *
     * @return Boolean TRUE on success or FALSE on failure
     */
    function open(StreamMode $mode);

    /**
     * Reads the specified number of bytes from the current position
     *
     * If the current position is the end-of-file, you must return an empty
     * string.
     *
     * @param  integer $count The number of bytes
     *
     * @return string
     */
    function read($count);

    /**
     * Writes the specified data
     *
     * Don't forget to update the current position of the stream by number of
     * bytes that were successfully written.
     *
     * @param  string $data
     *
     * @return integer The number of bytes that were successfully written
     */
    function write($data);

    /**
     * Closes the stream
     *
     * It must free all the resources. If there is any data to flush, you
     * should do so
     *
     * @return void
     */
    function close();

    /**
     * Flushes the output
     *
     * If you have cached data that is not yet stored into the underlying
     * storage, you should do so now
     *
     * @return Boolean TRUE on success or FALSE on failure
     */
    function flush();

    /**
     * Seeks to the specified offset
     *
     * @param  integer $offset
     * @param  integer $whence
     *
     * @return Boolean
     */
    function seek($offset, $whence = SEEK_SET);

    /**
     * Returns the current position
     *
     * @return integer
     */
    function tell();

    /**
     * Indicates whether the current position is the end-of-file
     *
     * @return Boolean
     */
    function eof();
}
