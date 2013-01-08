<?php

namespace Gaufrette\Adapter;

use Gaufrette\File;
use Gaufrette\Adapter;
use \MongoGridFS as MongoGridFs;
use \MongoDate;

/**
 * Adapter for the GridFS filesystem on MongoDB database
 *
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class GridFS implements Adapter,
                        ChecksumCalculator,
                        MetadataSupporter,
                        ListKeysAware
{
    protected $gridFS = null;

    /**
     * Constructor
     *
     * @param \MongoGridFS $gridFS
     */
    public function __construct(MongoGridFs $gridFS)
    {
        $this->gridFS = $gridFS;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $gridFile = $this->find($key);

        return ($gridFile) ? $gridFile->getBytes() : false;
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content)
    {
        if ($this->exists($key)) {
            $this->delete($key);
        }

        $id = $this->gridFS->storeBytes($content);
        $gridFile = $this->gridFS->findOne(array('_id' => $id));

        return $gridFile->getSize();
    }

    /**
     * {@inheritDoc}
     */
    public function readFile($key)
    {
        $gridFile = $this->gridFS->findOne(array('filename' => $key));
        $file = new File($key);                
        $file->setContent($gridFile->getBytes());
        
        return $file;
    }
    
    /**
     * {@inheritDoc}
     */
    public function writeFile($file)
    {
        $key = $file->getKey();
        if ($this->exists($key)) {
            $this->delete($key);
        }
        
        $gridMetadata = array_replace_recursive(array('date' => new MongoDate()), array('metadata' => $file->getMetadata()), array('filename' => $key));
        $id = $this->gridFS->storeBytes($file->getContent(), $gridMetadata);
        $gridfsFile = $this->gridFS->findOne(array('_id' => $id));
        $file->setSize($gridfsFile->getSize());
        
        return $file;
    }
    
    /**
     * {@inheritDoc}
     */
    public function isDirectory($key)
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $bytes = $this->write($targetKey, $this->read($sourceKey));
        $this->delete($sourceKey);

        return (boolean) $bytes;
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return (boolean) $this->find($key);
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $keys   = array();
        $cursor = $this->gridFS->find(array(), array('filename'));

        foreach ($cursor as $file) {
            $keys[] = $file->getFilename();
        }

        return $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        $file = $this->find($key, array('date'));

        return ($file) ? $file->file['date']->sec : false;
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        $file = $this->find($key, array('md5'));

        return ($file) ? $file->file['md5'] : false;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $gridFile = $this->find($key, array('_id'));

        return $gridFile && $this->gridFS->delete($gridFile->file['_id']);
    }
    
    private function find($key, array $fields = array())
    {
        return $this->gridFS->findOne($key, $fields);
    }

    /**
     * {@inheritDoc}
     */
    public function listKeys($pattern = '')
    {
        $pattern = trim($pattern);

        if ('' == $pattern) {
            return array(
                'dirs' => array(),
                'keys' => $this->keys()
            );
        }

        $result = array(
            'dirs' => array(),
            'keys' => array()
        );

        $gridFiles = $this->gridFS->find(array(
            'filename' => new \MongoRegex(sprintf('/^%s/', $pattern))
        ));

        foreach ($gridFiles as $file) {
            $result['keys'][] = $file->getFilename();
        }

        return $result;
    }
        
    public function isMetadataKeyAllowed($metaKey)
    {
        //GridFS accepts any metadata key
        return true;    
    }
    
    
}
