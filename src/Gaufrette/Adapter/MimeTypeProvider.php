<?php

namespace Gaufrette\Adapter;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\InvalidKey;
use Gaufrette\Exception\StorageFailure;

/**
 * Interface which add mime type provider support to adapter.
 *
 * @author Gildas Quemener <gildas.quemener@gmail.com>
 */
interface MimeTypeProvider
{
    /**
     * Returns the mime type of the specified key.
     *
     * @param string $key
     *
     * @return string
     *
     * @throws InvalidKey     If the key is invalid or malformed.
     * @throws FileNotFound   If the key does not exist.
     * @throws StorageFailure If the underlying storage fails (adapter should not leak exceptions)
     */
    public function mimeType($key);
}
