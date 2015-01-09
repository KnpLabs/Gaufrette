<?php

namespace Gaufrette\Stream;

use Gaufrette\Stream;
use Gaufrette\StreamMode;

/**
 * Local stream.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Local implements Stream
{
    private $path;
    private $mode;
    private $fileHandle;
    private $mkdirMode;

    /**
     * @param string $path
     * @param int    $mkdirMode
     */
    public function __construct($path, $mkdirMode = 0755)
    {
        $this->path = $path;
        $this->mkdirMode = $mkdirMode;
    }

    /**
     * {@inheritdoc}
     */
    public function open(StreamMode $mode)
    {
        $baseDirPath = \Gaufrette\Util\Path::dirname($this->path);
        if ($mode->allowsWrite() && !is_dir($baseDirPath)) {
            @mkdir($baseDirPath, $this->mkdirMode, true);
        }
        try {
            $fileHandle = @fopen($this->path, $mode->getMode());
        } catch (\Exception $e) {
            $fileHandle = false;
        }

        if (false === $fileHandle) {
            throw new \RuntimeException(sprintf('File "%s" cannot be opened', $this->path));
        }

        $this->mode = $mode;
        $this->fileHandle = $fileHandle;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($count)
    {
        if (!$this->fileHandle) {
            return false;
        }

        if (false === $this->mode->allowsRead()) {
            throw new \LogicException('The stream does not allow read.');
        }

        return fread($this->fileHandle, $count);
    }

    /**
     * {@inheritdoc}
     */
    public function write($data)
    {
        if (!$this->fileHandle) {
            return false;
        }

        if (false === $this->mode->allowsWrite()) {
            throw new \LogicException('The stream does not allow write.');
        }

        return fwrite($this->fileHandle, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if (!$this->fileHandle) {
            return false;
        }

        $closed = fclose($this->fileHandle);

        if ($closed) {
            $this->mode = null;
            $this->fileHandle = null;
        }

        return $closed;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if ($this->fileHandle) {
            return fflush($this->fileHandle);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if ($this->fileHandle) {
            return 0 === fseek($this->fileHandle, $offset, $whence);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if ($this->fileHandle) {
            return ftell($this->fileHandle);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        if ($this->fileHandle) {
            return feof($this->fileHandle);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stat()
    {
        if ($this->fileHandle) {
            return fstat($this->fileHandle);
        } elseif (!is_resource($this->fileHandle) && is_dir($this->path)) {
            return stat($this->path);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function cast($castAs)
    {
        if ($this->fileHandle) {
            return $this->fileHandle;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function unlink()
    {
        if ($this->mode && $this->mode->impliesExistingContentDeletion()) {
            return @unlink($this->path);
        }

        return false;
    }
}
