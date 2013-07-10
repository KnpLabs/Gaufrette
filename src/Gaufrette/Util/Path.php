<?php

namespace Gaufrette\Util;

/**
 * Path utils
 *
 * @package Gaufrette
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class Path
{
    /**
     * Normalizes the given path
     *
     * @param string $path
     *
     * @return string
     */
    public static function normalize($path)
    {
        $path   = str_replace('\\', '/', $path);
        $prefix = static::getAbsolutePrefix($path);
        $path   = substr($path, strlen($prefix));
        $parts  = array_filter(explode('/', $path), 'strlen');
        $tokens = array();

        foreach ($parts as $part) {
            switch ($part) {
                case '.':
                    continue;
                case '..':
                    if (0 !== count($tokens)) {
                        array_pop($tokens);
                        continue;
                    } elseif (!empty($prefix)) {
                        continue;
                    }
                default:
                    $tokens[] = $part;
            }
        }

        return $prefix . implode('/', $tokens);
    }

    /**
     * Apply directory levels to given path
     *
     * @param string $path
     * @param integer $levels
     *
     * @return string
     */
    public static function applyDirectoryLevels($path, $levels = 1)
    {
        $levels     = (int) $levels;
        $tokens     = explode('/', $path);
        $basename   = array_pop($tokens);
        $filename   = pathinfo($basename, PATHINFO_FILENAME);
        $name       = $filename . str_repeat('0', $levels);

        for ($i = 1; $i <= $levels; $i++) {
            $tokens[] = substr($name, 0, $i);
        }

        $tokens[]   = $basename;

        return implode('/', $tokens);

    }

    /**
     * Indicates whether the given path is absolute or not
     *
     * @param string $path A normalized path
     *
     * @return boolean
     */
    public static function isAbsolute($path)
    {
        return '' !== static::getAbsolutePrefix($path);
    }

    /**
     * Returns the absolute prefix of the given path
     *
     * @param string $path A normalized path
     *
     * @return string
     */
    public static function getAbsolutePrefix($path)
    {
        preg_match('|^(?P<prefix>([a-zA-Z]:)?/)|', $path, $matches);

        if (empty($matches['prefix'])) {
            return '';
        }

        return strtolower($matches['prefix']);
    }
}
