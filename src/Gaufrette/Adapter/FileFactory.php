<?php

namespace Gaufrette\Adapter;

use Gaufrette\File;
use Gaufrette\Filesystem;

/**
 * Interface for the file creation class.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
interface FileFactory
{
    /**
     * Creates a new File instance and returns it.
     */
    public function createFile(string $key, Filesystem $filesystem): File;
}
