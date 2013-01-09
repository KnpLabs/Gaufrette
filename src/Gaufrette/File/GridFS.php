<?php
namespace Gaufrette\File;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\File;
use \MongoGridFSFile;

/**
 * Points to a file in a filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class GridFS extends File
{
    private $gridFSFile = null;
    
    /**
     * Constructor
     *
     * @param string     $key
     * @param Filesystem $filesystem
     */
    public function __construct($key, MongoGridFSFile $gridFSFile = null)
    {
        $this->key = $key;
        $this->gridFSFile = $gridFSFile;
    }

    /**
     * Returns the content
     *
     * @return string
     */
    public function getContent()
    {
        if (isset($this->content)) {
            return $this->content;
        }
        
        //This operation is lazy and should not be called before the bytes are actually needed in app.
        return $this->gridFSFile->getBytes();
    }  
    
}
