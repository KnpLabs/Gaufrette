<?php

namespace Gaufrette\Adapter;

/**
 * Interface which add mime type provider support to adapter.
 *
 * @author Gildas Quemener <gildas.quemener@gmail.com>
 */
interface MimeTypeProvider
{
    /**
     * @return false|string the mime type of the specified key.
     */
    public function mimeType(string $key): string|bool;
}
