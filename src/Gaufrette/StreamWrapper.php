<?php

namespace Gaufrette;

/**
 * Stream wrapper class for the Gaufrette filesystems
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class StreamWrapper
{
    static private $filesystemMap;

    private $stream;

    /**
     * Defines the filesystem map
     *
     * @param  FilesystemMap $map
     */
    static public function setFilesystemMap(FilesystemMap $map)
    {
        static::$filesystemMap = $map;
    }

    /**
     * Returns the filesystem map
     *
     * @return FilesystemMap $map
     */
    static public function getFilesystemMap()
    {
        if (null === static::$filesystemMap) {
            static::$filesystemMap = static::createFilesystemMap();
        }

        return static::$filesystemMap;
    }

    /**
     * Registers the stream wrapper to handle the specified scheme
     *
     * @param  string $schema Default is gaufrette
     */
    static public function register($scheme = 'gaufrette')
    {
        @stream_wrapper_unregister($scheme);

        if ( ! stream_wrapper_register($scheme, __CLASS__)) {
            throw new \RuntimeException(sprintf(
                'Could not register stream wrapper class %s for scheme %s.',
                __CLASS__,
                $scheme
            ));
        }
    }

    /**
     * Binds the given filesystem to the specified domain
     *
     * @param  string     $domain
     * @param  Filesystem $filesystem
     */
    static public function bindFilesystem($domain, Filesystem $filesystem)
    {
        static::$filesystems[$domain] = $filesystem;
    }

    /**
     * Returns the filesystem bound to the specified domain
     *
     * @param  string $domain
     *
     * @return Filesystem
     */
    static public function getFilesystem($domain)
    {
        if (empty(static::$filesystems[$domain])) {
            throw new \InvalidArgumentException(sprintf(
                'There is no filesystem bound to the specified domain (%s).',
                $domain
            ));
        }

        return static::$filesystems[$domain];
    }

    public function stream_open($path, $mode)
    {
        $this->stream = $this->createStream($path);

        return $this->stream->open($this->createStreamMode($mode));
    }

    public function stream_read($count)
    {
        return $this->stream->read($count);
    }

    public function stream_write($data)
    {
        return $this->stream->write($data);
    }

    public function stream_close()
    {
        $this->stream->close();
    }

    public function stream_flush()
    {
        return $this->stream->flush();
    }

    public function stream_seek($offset, $whence = SEEK_SET)
    {
        return $this->stream->seek($offset, $whence);
    }

    public function stream_tell()
    {
        return $this->stream->tell();
    }

    public function stream_eof()
    {
        return $this->stream->eof();
    }

    public function stream_stat()
    {
        return null;
    }

    protected function createStream($path)
    {
        $parts = array_merge(
            array(
                'scheme'    => null,
                'host'      => null,
                'path'      => null,
                'query'     => null,
                'fragment'  => null,
            ),
            parse_url($path)
        );

        $domain = $parts['host'];
        $key    = substr($parts['path'], 1);

        if (null !== $parts['query']) {
            $key.= '?' . $parts['query'];
        }

        if (null !== $parts['fragment']) {
            $key.= '#' . $parts['fragment'];
        }

        if (empty($domain) || empty($key)) {
            throw new \InvalidArgumentException(sprintf(
                'The specified path (%s) is invalid.',
                $path
            ));
        }

        return static::getFilesystemMap()->get($domain)->createFileStream($key);
    }

    protected function createStreamMode($mode)
    {
        return new StreamMode($mode);
    }

    static protected function createFilesystemMap()
    {
        return new FilesystemMap();
    }
}
