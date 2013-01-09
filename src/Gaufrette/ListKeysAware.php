<?php

namespace Gaufrette;

/**
 * interface that adds support of native listKeys to adapter
 *
 * @author Andrew Tch <andrew.tchircoff@gmail.com>
 */
interface ListKeysAware
{
    /**
     * Lists keys beginning with pattern given
     * (no wildcard / regex matching)
     *
     * @param string $pattern
     * @return array
     */
    public function listKeys($pattern = '');
}