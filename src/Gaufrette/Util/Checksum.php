<?php

namespace Gaufrette\Util;

/**
 * Checksum utils.
 *
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class Checksum
{
    /**
     * Returns the checksum of the given content and algorithm.
     *
     * @param string $content
     * @param string $algo
     *
     * @return string
     */
    public static function fromContent($content, $algo = 'md5')
    {
        return hash($algo, $content);
    }

    /**
     * Returns the checksum of the specified file and algorithm.
     *
     * @param string $filename
     * @param string $algo
     *
     * @return string
     */
    public static function fromFile($filename, $algo = 'md5')
    {
        return hash_file($algo, $filename);
    }
}
