<?php

namespace Gaufrette\Util;

/**
 * Path utils.
 *
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class Path
{
    /**
     * Normalizes the given path.
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalize($path)
    {
        $path = str_replace('\\', '/', $path);
        $prefix = static::getAbsolutePrefix($path);
        $path = substr($path, strlen($prefix));
        $parts = array_filter(explode('/', $path), 'strlen');
        $tokens = [];

        foreach ($parts as $part) {
            switch ($part) {
                case '.':
                    continue 2;
                case '..':
                    if (0 !== count($tokens)) {
                        array_pop($tokens);

                        continue 2;
                    } elseif (!empty($prefix)) {
                        continue 2;
                    }
                default:
                    $tokens[] = $part;
            }
        }

        return $prefix . implode('/', $tokens);
    }

    /**
     * Indicates whether the given path is absolute or not.
     *
     * @param string $path A normalized path
     *
     * @return bool
     */
    public static function isAbsolute($path)
    {
        return '' !== static::getAbsolutePrefix($path);
    }

    /**
     * Returns the absolute prefix of the given path.
     *
     * @param string $path A normalized path
     *
     * @return string
     */
    public static function getAbsolutePrefix($path)
    {
        preg_match('|^(?P<prefix>([a-zA-Z]+:)?//?)|', $path, $matches);

        if (empty($matches['prefix'])) {
            return '';
        }

        return strtolower($matches['prefix']);
    }

    /**
     * Wrap native dirname function in order to handle only UNIX-style paths
     *
     * @param string $path
     *
     * @return string
     *
     * @see http://php.net/manual/en/function.dirname.php
     */
    public static function dirname($path)
    {
        return str_replace('\\', '/', \dirname($path));
    }
}
