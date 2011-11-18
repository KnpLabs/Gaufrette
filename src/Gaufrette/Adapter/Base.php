<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Filesystem;
use Gaufrette\File;
use Gaufrette\FileStream;

abstract class Base implements Adapter
{
    /**
     * {@inheritDoc}
     */
    public function createFile($key, Filesystem $filesystem)
    {
        return new File($key, $filesystem);
    }

    /**
     * {@inheritDoc}
     */
    public function createFileStream($key, Filesystem $filesystem)
    {
        return new FileStream\InMemoryBuffer($this, $key);
    }
}
