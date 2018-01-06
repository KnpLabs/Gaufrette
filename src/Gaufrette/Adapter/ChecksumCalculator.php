<?php

namespace Gaufrette\Adapter;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\InvalidKey;
use Gaufrette\Exception\StorageFailure;

/**
 * Interface which add checksum calculation support to adapter.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
interface ChecksumCalculator
{
    /**
     * Returns the checksum of the specified key.
     *
     * @param string $key
     *
     * @return string
     *
     * @throws InvalidKey     If the key is invalid or malformed.
     * @throws FileNotFound   If the key does not exist.
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function checksum($key);
}
