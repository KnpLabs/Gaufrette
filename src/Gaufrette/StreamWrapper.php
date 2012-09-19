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

    static protected function createFilesystemMap()
    {
        return new FilesystemMap();
    }

    public function stream_open($path, $mode)
    {
        $this->stream = $this->createStream($path);

        return $this->stream->open($this->createStreamMode($mode));
    }

    /**
     * @param int $bytes
     * @return mixed
     */
    public function stream_read($bytes)
    {
        if ($this->stream) {
            return $this->stream->read($bytes);
        }

        return false;
    }

    /**
     * @param string $data
     * @return int
     */
    public function stream_write($data)
    {
        if ($this->stream) {
            return $this->stream->write($data);
        }

        return 0;
    }

    public function stream_close()
    {
        if ($this->stream) {
            $this->stream->close();
        }
    }

    /**
     * @return boolean
     */
    public function stream_flush()
    {
        if ($this->stream) {
            return $this->stream->flush();
        }

        return false;
    }

    /**
     * @param int $offset
     * @param int $whence - one of values [SEEK_SET, SEEK_CUR, SEEK_END]
     * @return boolean
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        if ($this->stream) {
            return $this->stream->seek($offset, $whence);
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function stream_tell()
    {
        if ($this->stream) {
            return $this->stream->tell();
        }

        return false;
    }

    /**
     * @return boolean
     */
    public function stream_eof()
    {
        if ($this->stream) {
            return $this->stream->eof();
        }

        return true;
    }

    /**
     * @return mixed
     */
    public function stream_stat()
    {
        if ($this->stream) {
            return $this->stream->stat();
        }

        return false;
    }

    /**
     * @param string $path
     * @param int $flags
     * @return mixed
     * @todo handle $flags parameter
     */
    public function url_stat($path, $flags)
    {
        $stream = $this->createStream($path);

        return $stream->stat();
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
}
