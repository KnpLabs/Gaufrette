<?php
namespace Gaufrette\Filesystem;

use Gaufrette\File as AbstractFile;
use Gaufrette\File\GridFS as File;
use Gaufrette\Filesystem;
use Gaufrette\MetadataSupporter;
use \MongoGridFS;
use \MongoDate;

/**
 * Adapter for the GridFS filesystem on MongoDB database
 *
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class GridFS implements Filesystem, MetadataSupporter //, ListKeysAware
{
    protected $gridFS;
    
    /**
     * Constructor
     *
     * @param \MongoGridFS $gridFS
     */
    public function __construct(MongoGridFS $gridFS)
    {
        $this->gridFS = $gridFS;
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
    public function read($key)
    {
        $gridFSFile = $this->gridFS->findOne(array('key' => $key));
        $file = new File($gridFSFile->file['key'], $gridFSFile);
        //Set data for file (do not set content, it's lazy)
        $file->setMetadata($gridFSFile->file['metadata']);
        $file->setName($gridFSFile->file['name']);
        $file->setDate($gridFSFile->file['date']);
        $file->setChecksum($gridFSFile->file['md5']);
        return $file;
    }
    
    /**
     * {@inheritDoc}
     */
    public function write(AbstractFile $file)
    {
        $key = $file->getKey();
        if (! isset($key) || strlen($key."") < 1) {
            throw new \InvalidArgumentException(sprintf('Key is not set for file. Cannot write file.'));
        }
        if (strlen($file->getContent()) < 1) {
            throw new \InvalidArgumentException(sprintf('Content is not for file "%s". Cannot write file.'), $key);            
        }        
        
        $key = $file->getKey();
        if ($this->exists($key)) {
            $this->delete($key);
        }
        
        $gridMetadata = array_replace_recursive(array('date' => new MongoDate()),
                                                array('name' => $file->getName()),
                                                array('metadata' => $file->getMetadata()),
                                                array('key' => $key));
        $id = $this->gridFS->storeBytes($file->getContent(), $gridMetadata);
        $gridfsFile = $this->gridFS->findOne(array('_id' => $id));
        $file->setSize($gridfsFile->getSize());
        
        return $file;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $file = $this->read($sourceKey);        
        $file->setKey($targetKey);
        $targetFile = $this->write($file);
        if (isset($targetFile) && is_object($targetFile)) {
            $this->delete($sourceKey);
            return true;
        }
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $keys   = array();
        $cursor = $this->gridFS->find(array(), array('key'));

        foreach ($cursor as $gridFSFile) {
            $keys[] = $gridFSFile->getFilename();
        }

        return $keys;
    }

    public function checksum($key)
    {
        $gridFSFile = $this->find($key, array('md5'));

        return ($gridFSFile) ? $gridFSFile->file['md5'] : false;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $gridFSFile = $this->find($key, array('_id'));

        return $gridFSFile && $this->gridFS->delete($gridFSFile->file['_id']);
    }
    
    private function find($key, array $fields = array())
    {
        return $this->gridFS->findOne($key, $fields);
    }

    /**
     * {@inheritDoc}
     */
    /*
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
    */

    /**
     * Factory method for a new empty file object
     *
     * @param string key
     *
     * @param File file
     */
    public function createFile($key)
    {
        return new File($key);
    }
        
    public function isMetadataKeyAllowed($metaKey)
    {
        //GridFS accepts any metadata key
        return true;    
    }
    
    
}
