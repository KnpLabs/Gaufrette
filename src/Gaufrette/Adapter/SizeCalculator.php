<?php

namespace Gaufrette\Adapter;
use Gaufrette\Exception\StorageFailure;

/**
 * Interface which add size calculation support to adapter.
 *
 * @author Markus Poerschke <markus@eluceo.de>
 */
interface SizeCalculator
{
    /**
     * Returns the size of the specified key.
     *
     * @param string $key
     *
     * @return int
     *
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function size($key);
}
