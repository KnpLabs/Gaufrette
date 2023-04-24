<?php

namespace Gaufrette\Util;

/**
 * Utility class for file sizes.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Size
{
    /**
     * Returns the size in bytes from the given content.
     *
     * @todo handle the case the mbstring is not loaded
     */
    public static function fromContent(string $content): int
    {
        // Make sure to get the real length in byte and not
        // accidentally mistake some bytes as a UTF BOM.
        return mb_strlen($content, '8bit');
    }

    /**
     * Returns the size in bytes from the given file.
     */
    public static function fromFile(string $filename): false|int
    {
        return filesize($filename);
    }

    /**
     * Returns the size in bytes from the given resource.
     *
     * @param resource $handle
     */
    public static function fromResource($handle): int
    {
        $cStat = fstat($handle);
        // if the resource is a remote file, $cStat will be false
        return $cStat ? $cStat['size'] : 0;
    }
}
