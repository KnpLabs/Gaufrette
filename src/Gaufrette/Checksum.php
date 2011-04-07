<?php

namespace Gaufrette;

/**
 * Checksum utils
 *
 * @package Gaufrette
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class Checksum
{
    /**
     * Returns the checksum of the given content
     *
     * @param  string $content
     *
     * @return string
     */
    static public function fromContent($content)
    {
        return md5($content);
    }

    /**
     * Returns the checksum of the specified file
     *
     * @param  string $filename
     *
     * @return string
     */
    static public function fromFile($filename)
    {
        return md5_file($filename);
    }

    /**
     * Indicates whether the specified checksum matches the given content
     *
     * @param  string $checksum
     * @param  string $content
     *
     * @return boolean
     */
    static public function matchesContent($checksum, $content)
    {
        return $checksum === static::fromContent($content);
    }
}
