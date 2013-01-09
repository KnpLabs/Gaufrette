<?php

namespace Gaufrette;

/**
 * Interface for the file creation class
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
interface FileFactory
{
    /**
     * Creates a new File instance and returns it
     *
     * @param string     $key
     * @param Filesystem $filesystem
     *
     * @return File
     */
    public function createFile($key, $content = null);
}
