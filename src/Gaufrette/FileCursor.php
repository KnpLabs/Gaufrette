<?php

namespace Gaufrette;

use Gaufrette\Filesystem;
use Gaufrette\File;

/**
 * Cursor for looping through several files without building a huge assoc array out of them
 * This approach saves runtime memory which is useful, for example, in command line scripting
 * of large file sets
 *
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 */
abstract class FileCursor implements \Iterator
{
    protected $filesystem;
    protected $parentCursor = null;

    public function __construct(\Iterator $parentCursor, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->parentCursor = $parentCursor;
    }

    public function rewind()
    {
        $this->parentCursor->rewind();
    }

    /**
     * Overload at least this function in subclass to return a proper fully prepared File object
     * @return \Gaufrette\File
     */
    public function current()
    {
        return $this->parentCursor->current();
    }

    public function key()
    {
        return $this->parentCursor->key();
    }

    public function next()
    {
        $this->parentCursor->next();
    }

    public function valid()
    {
        return $this->parentCursor->valid();
    }

}
