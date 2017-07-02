<?php

namespace Gaufrette\Adapter;

use Gaufrette\Exception\StorageFailure;

/**
 * interface that adds support of native listKeys to adapter.
 *
 * @author Andrew Tch <andrew.tchircoff@gmail.com>
 */
interface ListKeysAware
{
    /**
     * Lists keys beginning with pattern given
     * (no wildcard / regex matching).
     *
     * @param string $prefix
     *
     * @return array
     *
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function listKeys($prefix = '');
}
