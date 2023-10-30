<?php

namespace Gaufrette;

/**
 * Stream wrapper class for the Gaufrette filesystems.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class StreamWrapper
{
    private static FilesystemMap $filesystemMap;

    private Stream $stream;

    /**
     * @var ?resource
     * @see https://www.php.net/manual/en/class.streamwrapper.php#streamwrapper.props.context
     */
    public $context;

    /**
     * Defines the filesystem map.
     */
    public static function setFilesystemMap(FilesystemMap $map): void
    {
        self::$filesystemMap = $map;
    }

    /**
     * Returns the filesystem map.
     */
    public static function getFilesystemMap(): FilesystemMap
    {
        if (false === isset(self::$filesystemMap)) {
            self::$filesystemMap = self::createFilesystemMap();
        }

        return self::$filesystemMap;
    }

    /**
     * Registers the stream wrapper to handle the specified scheme.
     */
    public static function register(string $scheme = 'gaufrette'): void
    {
        self::streamWrapperUnregister($scheme);

        if (!self::streamWrapperRegister($scheme, __CLASS__)) {
            throw new \RuntimeException(sprintf(
                'Could not register stream wrapper class %s for scheme %s.',
                __CLASS__,
                $scheme
            ));
        }
    }

    protected static function createFilesystemMap(): FilesystemMap
    {
        return new FilesystemMap();
    }

    /**
     * @param string $scheme Protocol scheme
     */
    protected static function streamWrapperUnregister(string $scheme): bool
    {
        if (in_array($scheme, stream_get_wrappers())) {
            return stream_wrapper_unregister($scheme);
        }

        return false;
    }

    /**
     * @param string $scheme Protocol scheme
     */
    protected static function streamWrapperRegister(string $scheme, string $className): bool
    {
        return stream_wrapper_register($scheme, $className);
    }

    /**
     * @param STREAM_USE_PATH|STREAM_REPORT_ERRORS|9 $options "9" is the result of a bitwize operation between STREAM_USE_PATH and STREAM_REPORT_ERRORS (STREAM_USE_PATH|STREAM_REPORT_ERRORS).
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path = null): bool
    {
        $this->stream = $this->createStream($path);

        return $this->stream->open($this->createStreamMode($mode));
    }

    /**
     * @return string|false
     */
    public function stream_read(int $count): string|bool
    {
        if (isset($this->stream)) {
            return $this->stream->read($count);
        }

        return false;
    }

    public function stream_write(string $data): int
    {
        if (isset($this->stream)) {
            return $this->stream->write($data);
        }

        return 0;
    }

    public function stream_close(): void
    {
        if (isset($this->stream)) {
            $this->stream->close();
        }
    }

    public function stream_flush(): bool
    {
        if (isset($this->stream)) {
            return $this->stream->flush();
        }

        return false;
    }

    /**
     * @param SEEK_SET|SEEK_CUR|SEEK_END $whence
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        if (isset($this->stream)) {
            return $this->stream->seek($offset, $whence);
        }

        return false;
    }

    /**
     * Retrieve the current position of a stream
     */
    public function stream_tell(): int
    {
        if (isset($this->stream)) {
            return $this->stream->tell();
        }

        return 0;
    }

    public function stream_eof(): bool
    {
        if (isset($this->stream)) {
            return $this->stream->eof();
        }

        return true;
    }

    /**
     * @return array<string, mixed>|false
     */
    public function stream_stat(): array|bool
    {
        if (isset($this->stream)) {
            return $this->stream->stat();
        }

        return false;
    }

    /**
     * @return array<string, mixed>|false
     *
     * @TODO handle $flags parameter
     */
    public function url_stat(string $path, int $flags): array|bool
    {
        $stream = $this->createStream($path);

        try {
            $stream->open($this->createStreamMode('r+'));
        } catch (\RuntimeException $e) {
        }

        return $stream->stat();
    }

    public function unlink(string $path): bool
    {
        $stream = $this->createStream($path);

        try {
            $stream->open($this->createStreamMode('w+'));
        } catch (\RuntimeException $e) {
            return false;
        }

        return $stream->unlink();
    }

    /**
     * @return resource|false
     * @param STREAM_CAST_FOR_SELECT|STREAM_CAST_AS_STREAM $castAs
     */
    public function stream_cast(int $castAs)
    {
        if (isset($this->stream)) {
            return $this->stream->cast($castAs);
        }

        return false;
    }

    protected function createStream(string $path): Stream
    {
        $parts = array_merge(
            [
                'scheme' => null,
                'host' => null,
                'path' => null,
                'query' => null,
                'fragment' => null,
            ],
            parse_url($path) ?: []
        );

        $domain = $parts['host'];
        $key = !empty($parts['path']) ? substr($parts['path'], 1) : '';

        if (null !== $parts['query']) {
            $key .= '?' . $parts['query'];
        }

        if (null !== $parts['fragment']) {
            $key .= '#' . $parts['fragment'];
        }

        if (empty($domain) || empty($key)) {
            throw new \InvalidArgumentException(sprintf(
                'The specified path (%s) is invalid.',
                $path
            ));
        }

        return self::getFilesystemMap()
            ->get($domain)
            ->createStream($key)
        ;
    }

    protected function createStreamMode(string $mode): StreamMode
    {
        return new StreamMode($mode);
    }
}
