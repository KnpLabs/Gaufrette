<?php

namespace Gaufrette\Adapter;

use Gaufrette\Stream;

/**
 * Interface for the stream creation class.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
interface StreamFactory
{
    /**
     * Creates a new stream instance of the specified file.
     */
    public function createStream(string $key): Stream;
}
