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
class GridFS extends FileCursor
{
    /**
     * {@InheritDoc}
     */
    public function current()
    {
        $r = $this->parentCursor->current();
        $key = $r->file['key'];
        $file = new File($key, $this->filesystem);
        $file->setMetadata($r->file['metadata']);
        $file->setName($r->file['filename']);
        $file->setCreated(new \DateTime("@".$r->file['uploadDate']->sec));
        $file->setSize($r->file['length']);

        return $file;
    }
}