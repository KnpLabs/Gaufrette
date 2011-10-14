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
                $this->position   = 0;
                $this->allowRead  = true;
                $this->allowWrite = $modePlus;
                break;
            case 'w':
                // the file is truncated to zero length
                $this->filesystem->write($this->key, '', true);
                $this->content    = '';
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
                $this->position   = strlen($this->content);
                $this->allowRead  = $modePlus;
                $this->allowWrite = true;
                break;
            case 'x':
                if ($this->filesystem->has($this->key)) {
                    return false;
                }
                $this->filesystem->write($this->key, '');
                $this->content    = '';
                $this->position   = 0;
                $this->allowRead  = $modePlus;
                $this->allowWrite = true;
                break;
            case 'c':
                if ($this->filesystem->has($this->key)) {
                    $this->content  = $this->filesystem->read($this->key);
                } else {
                    $this->filesystem->write($this->key, '');
                    $this->content    = '';
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
    }

    public function write($data)
    {
    }

    public function close()
    {
    }

    public function seek($offset, $whence = SEEK_SET)
    {
    }

    public function tell()
    {
    }

    public function flush()
    {
    }

    public function eof()
    {
    }
}
