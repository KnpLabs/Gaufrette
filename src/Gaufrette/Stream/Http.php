<?php
namespace Gaufrette\Stream;

use Gaufrette\Stream;
use Gaufrette\StreamMode;

/**
 * Http stream.
 * Similar to Local stream, but the HTTP protocol
 * does not allow simultaneous reading and writing.
 *
 * @author Jon Skarpeteig <jon.skarpeteig@gmail.com>
 */
class Http extends Local implements Stream
{

    protected $context;

    /**
     * Constructor
     *
     * @param string $path            
     */
    public function __construct($path, $context = null)
    {
        $this->context = ($context === null) ? stream_context_create() : $context;
        $this->path = $path;
    }

    /**
     * {@inheritDoc}
     */
    public function open(StreamMode $mode)
    {
        if ($mode->allowsRead() && $mode->allowsWrite()) {
            throw new \RuntimeException(sprintf('File "%s" cannot be opened with read and write mode simultaneously', $this->path));
        }
        
        try {
            $fileHandle = @fopen($this->path, $mode->getMode(), false, $this->context);
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
     * {@inheritDoc}
     */
    public function stat()
    {
        if ($this->fileHandle) {
            
            /**
             * 0 dev device number
             * 1 ino inode number *
             * 2 mode inode protection mode
             * 3 nlink number of links
             * 4 uid userid of owner *
             * 5 gid groupid of owner *
             * 6 rdev device type, if inode device
             * 7 size size in bytes
             * 8 atime time of last access (Unix timestamp)
             * 9 mtime time of last modification (Unix timestamp)
             * 10 ctime time of last inode change (Unix timestamp)
             * 11 blksize blocksize of filesystem IO **
             * 12 blocks number of 512-byte blocks allocated **
             * On Windows this will always be 0.
             * * Only valid on systems supporting the st_blksize type - other systems (e.g. Windows) return -1.
             */
            
            /**
             * Directories must be a mode like 040777 (octal), and files a mode like 0100666.
             *
             * If you wish the file to be executable, use 7s instead of 6s. The last 3 digits
             * are exactly the same thing as what you pass to chmod. 040000 defines a directory,
             * and 0100000 defines a file.
             */
            
            return array(
                'dev' => 0,
                'ino' => 0,
                'mode' => 0100666,
                'nlink' => 0,
                'uid' => 0,
                'gid' => 0,
                'rdev ' => 0,
                'size' => filesize($this->path),
                'atime' => 0,
                'mtime' => filemtime($this->path),
                'ctime' => 0,
                'blksize' => - 1,
                'blocks' => - 1
            );
        }
        
        return false;
    }
}
