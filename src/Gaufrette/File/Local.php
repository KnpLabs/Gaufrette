<?php
namespace Gaufrette\File;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\File;

/**
 * Adapter for the local filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 */
class Local extends File
{
    private $path = null;
    
    /**
     * Constructor
     *
     * @param string $key
     * @param string $path to file in local fs
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    public function getPath()
    {
        return $this->path;
    }
    
    public function setPath($path)
    {
        $this->path = $path;
    }
        
    /**
     * Returns the content
     *
     * @return string content bytes
     */
    public function getContent()
    {
        if (isset($this->content)) {
            return $this->content;
        }
        if (isset($this->path)) {
            //Let's not read bytes into memory before it's absolutely necessary
            $content = file_get_contents($this->path);
            $this->setContent($content);

            return $content;
        }
        
        return null;
    }  

}
