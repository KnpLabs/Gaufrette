<?php

namespace Gaufrette\FileCursor;

use Gaufrette\FileCursor;
use Gaufrette\Filesystem;

/**
 * Cursor for looping through several files without building a huge associative
 * array out of them. This approach saves runtime memory which is useful, for
 * example, in command line scripting of large file sets.
 *
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 */
abstract class IteratorWrapper implements FileCursor
{
    protected $iterator;
    protected $filesystem;

    public function __construct(\Iterator $iterator, Filesystem $filesystem)
    {
        $this->iterator   = $iterator;
        $this->filesystem = $filesystem;
    }

    /**
     * Returns a File instance for the given current of the inner iterator
     *
     * @param  mixed $current
     *
     * @return File
     */
    abstract protected function createFile($current);

    /**
     * Delegates to the inner iterator
     *
     * @see Iterator::rewind()
     */
    public function rewind()
    {
        $this->iterator->rewind();
    }

    /**
     * Returns the file returned by the ->createFile() method called with the
     * current value of the inner iterator
     *
     * @return File
     */
    public function current()
    {
        return $this->createFile($this->iterator->current());
    }

    /**
     * Delegates to the inner iterator
     *
     * @see Iterator::key()
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * Delegates to the inner iterator
     *
     * @see Iterator::next()
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * Delegates to the inner iterator
     *
     * @see Iterator::valid()
     */
    public function valid()
    {
        return $this->iterator->valid();
    }
}
