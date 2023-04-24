<?php

namespace Gaufrette\Util;

/**
 * Checksum utils.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Checksum
{
    /**
     * Returns the checksum of the given content.
     */
    public static function fromContent(string $content): string
    {
        return md5($content);
    }

    /**
     * Returns the checksum of the specified file.
     */
    public static function fromFile(string $filename): false|string
    {
        return md5_file($filename);
    }
}
