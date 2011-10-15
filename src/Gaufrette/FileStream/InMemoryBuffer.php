<?php

namespace Gaufrette\FileStream;

use Gaufrette\FileStream;
use Gaufrette\Filesystem;

class InMemoryBuffer implements FileStream
{
    private $key;
    private $filesystem;
    private $binary;
    private $content;
    private $numBytes;
    private $position;
    private $allowRead;
    private $allowWrite;
    private $synchronized;

    /**
     * Constructor
     *
     * @param  File $file
     */
    public function __construct($key, Filesystem $filesystem)
    {
        $this->key        = $key;
        $this->filesystem = $filesystem;
    }

    /**
     * {@inheritDoc}
     */
    public function open($mode)
    {
        if ( ! preg_match('/^(?<group>[rwaxc])(?<plus>\+)?(?<binary>b)?$/', $mode, $matches)) {
            throw new \InvalidArgumentException(sprintf(
                'The specified mode (%s) is invalid.',
                $mode
            ));
        }

        $modeGroup  = $matches['group'];
        $modePlus   = isset($matches['plus']);
        $modeBinary = isset($matches['binary']);

        switch ($modeGroup) {
            case 'r':
                // the file must exist
                if ( ! $this->filesystem->has($this->key)) {
                    return false;
                }
                $this->content    = $this->filesystem->read($this->key);
                $this->numBytes   = strlen($this->content);
                $this->position   = 0;
                $this->allowRead  = true;
                $this->allowWrite = $modePlus;
                break;
            case 'w':
                // the file is truncated to zero length
                $this->filesystem->write($this->key, '', true);
                $this->content    = '';
                $this->numBytes   = 0;
                $this->position   = 0;
                $this->allowRead  = $modePlus;
                $this->allowWrite = true;
                break;
            case 'a':
                if ($this->filesystem->has($this->key)) {
                    $this->content = $this->filesystem->read($this->key);
                } else {
                    $this->filesystem->write($this->key, '');
                    $this->content = '';
                }
                $this->numBytes   = strlen($this->content);
                $this->position   = $this->numBytes;
                $this->allowRead  = $modePlus;
                $this->allowWrite = true;
                break;
            case 'x':
                if ($this->filesystem->has($this->key)) {
                    return false;
                }
                $this->filesystem->write($this->key, '');
                $this->content    = '';
                $this->numBytes   = 0;
                $this->position   = 0;
                $this->allowRead  = $modePlus;
                $this->allowWrite = true;
                break;
            case 'c':
                if ($this->filesystem->has($this->key)) {
                    $this->content  = $this->filesystem->read($this->key);
                    $this->numBytes = strlen($this->content);
                } else {
                    $this->filesystem->write($this->key, '');
                    $this->content  = '';
                    $this->numBytes = 0;
                }

                $this->position   = 0;
                $this->allowRead  = $modePlus;
                $this->allowWrite = true;
                break;
        }

        $this->synchronized = true;

        return true;
    }

    public function read($count)
    {
        if (false === $this->allowRead) {
            throw new \LogicException('The stream does not allow read.');
        }

        if (0 === $count) {
            return '';
        }

        $chunk = substr($this->content, $this->position, $count);

        $this->position+= $chunk;

        return $chunk;
    }

    public function write($data)
    {
        if (false === $this->allowWrite) {
            throw new \LogicException('The stream does not allow write.');
        }

        if ('' === $data) {
            return 0;
        }

        $numWrittenBytes = strlen($data);
        $newPosition     = $this->position + $numWrittenBytes;
        $newNumBytes     = $newPosition > $this->numBytes ? $newPosition : $this->numBytes;

        if ($this->eof()) {
            $this->numBytes+= $numWrittenBytes;
            $this->content.= $data;
        } else {
            $before = substr($this->content, 0, $this->position);
            $after  = $newNumBytes > $newPosition ? substr($this->content, $newPosition) : '';
            $this->content  = $before . $data . $after;
        }

        $this->position     = $newPosition;
        $this->numBytes     = $newNumBytes;
        $this->synchronized = false;

        return $numWrittenBytes;
    }

    public function close()
    {
        if ( ! $this->synchronized) {
            $this->flush();
        }
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;
                break;
            case SEEK_CUR:
                $this->position+= $offset;
                break;
            case SEEK_END:
                $this->position = $this->numBytes + $offset;
                break;
            default:
                throw new \InvalidArgumentException('Invalid $whence.');
        }
    }

    public function tell()
    {
        return $this->position;
    }

    public function flush()
    {
        if ($this->synchronized) {
            return true;
        }

        try {
            $this->filesystem->write($this->key, $this->content, true);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function eof()
    {
        return $this->position >= $this->numBytes;
    }
}
