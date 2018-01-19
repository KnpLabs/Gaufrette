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

        return $prefix.implode('/', $tokens);
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

    /**
     * UTF-8 aware parse_url() replacement.
     *
     * @param string $url to parse
     *
     * @return bool|array
     *
     * @see https://secure.php.net/manual/function.parse-url.php#114817
     */
    public static function parseUrl($url)
    {
        $encodedUrl = preg_replace_callback(
            '%[^:/@?&=#]+%usD',
            function ($matches)
            {
                return urlencode($matches[0]);
            },
            $url
        );

        $parts = parse_url($encodedUrl);

        if (false === $parts)
        {
            return false;
        }

        foreach ($parts as $name => $value)
        {
            $parts[$name] = urldecode($value);
        }

        return $parts;
    }
}
