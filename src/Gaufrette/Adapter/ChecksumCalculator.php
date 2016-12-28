<?php

namespace Gaufrette\Adapter;

/**
 * Interface which add checksum calculation support to adapter.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
interface ChecksumCalculator
{
    /**
     * Returns the checksum of the specified key and algorithm.
     *
     * @param string $key
     * @param string $algo
     *
     * @return string
     */
    public function checksum($key, $algo);
}
