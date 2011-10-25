<?php

namespace Gaufrette\FileCursor;

use Gaufrette\Filesystem;
use Gaufrette\FileCursor;
use Gaufrette\File;

/**
 * Helper class for looping files efficiently without assoc arrays
 *
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 */
class GridFS extends IteratorWrapper
{
    /**
     * {@InheritDoc}
     */
    protected function createFile($current)
    {
        $key  = $current->file['key'];
        $file = new File($key, $this->filesystem);
        $file->setName($current->file['filename']);
        $file->setCreated(new \DateTime("@".$current->file['uploadDate']->sec));
        $file->setSize($current->file['length']);

        if (isset($current->file['metadata'])) {
            $file->setMetadata($current->file['metadata']);
        }

        return $file;
    }
}
