<?php

namespace Gaufrette\FileStream;

use Gaufrette\FileStream;
use Gaufrette\Adapter;
use Gaufrette\StreamMode;
use Gaufrette\Util;

class InMemoryBuffer implements FileStream
{
    private $adapter;
    private $key;
    private $mode;
    private $content;
    private $numBytes;
    private $position;
    private $synchronized;

    /**
     * Constructor
     *
     * @param  Adapter $adapter The adapter managing the file to stream
     * @param  string  $key     The file key
     */
    public function __construct(Adapter $adapter, $key)
    {
        $this->adapter = $adapter;
        $this->key     = $key;
    }

    /**
     * {@inheritDoc}
     */
    public function open(StreamMode $mode)
    {
        $this->mode = $mode;

        $exists = $this->adapter->exists($this->key);

        if (($exists && !$mode->allowsExistingFileOpening())
            || (!$exists && !$mode->allowsNewFileOpening())) {
            return false;
        }

        if ($mode->impliesExistingContentDeletion()) {
            $this->adapter->write($this->key, '');
            $this->content = '';
        } else {
            $this->content = $this->adapter->read($this->key);
        }

        $this->numBytes = Util\Size::fromContent($this->content);
        $this->position = $mode->impliesPositioningCursorAtTheEnd() ? $this->numBytes : 0;

        $this->synchronized = true;

        return true;
    }

    public function read($count)
    {
        if (false === $this->mode->allowsRead()) {
            throw new \LogicException('The stream does not allow read.');
        }

        $chunk = substr($this->content, $this->position, $count);
        $this->position+= Util\Size::fromContent($chunk);

        return $chunk;
    }

    public function write($data)
    {
        if (false === $this->mode->allowsWrite()) {
            throw new \LogicException('The stream does not allow write.');
        }

        $numWrittenBytes = Util\Size::fromContent($data);

        $newPosition     = $this->position + $numWrittenBytes;
        $newNumBytes     = $newPosition > $this->numBytes ? $newPosition : $this->numBytes;

        if ($this->eof()) {
            $this->numBytes += $numWrittenBytes;
            if ($this->hasNewContentAtFurtherPosition()) {
                $data = str_pad($data, $this->position + strlen($data), " ", STR_PAD_LEFT);
            }
            $this->content .= $data;
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
                $this->position += $offset;
                break;
            case SEEK_END:
                $this->position = $this->numBytes + $offset;
                break;
            default:
                return false;
        }

        return true;
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
            $this->adapter->write($this->key, $this->content);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function eof()
    {
        return $this->position >= $this->numBytes;
    }

    /**
     * {@inheritDoc}
     */
    public function stat()
    {
        if ($this->adapter->exists($this->key)) {
            $time = $this->adapter->mtime($this->key);

            $stats = array(
                'dev'   => 1,
                'ino'   => 0,
                'mode'  => 33204,
                'nlink' => 1,
                'uid'   => 0,
                'gid'   => 0,
                'rdev'  => 0,
                'size'  => Util\Size::fromContent($this->adapter->read($this->key)),
                'atime' => $time,
                'mtime' => $time,
                'ctime' => $time,
                'blksize' => -1,
                'blocks'  => -1,
            );

            return array_merge(array_values($stats), $stats);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function cast($castAst)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function unlink()
    {
        if ($this->mode && $this->mode->impliesExistingContentDeletion()) {
            return $this->adapter->delete($this->key);
        }

        return false;
    }

    /**
     * @return Boolean
     */
    protected function hasNewContentAtFurtherPosition()
    {
        return $this->position > 0 && !$this->content;
    }
}
