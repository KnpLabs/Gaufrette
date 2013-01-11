<?php

namespace Gaufrette\Adapter;

use Gaufrette\File;
use Gaufrette\File\GridFS as GridFSFile;
use Gaufrette\Adapter;
use Gaufrette\FileFactory;
use Gaufrette\ChecksumCalculator;
use Gaufrette\MetadataSupporter;
use Gaufrette\ListKeysAware;
use \MongoGridFS;
use \MongoDate;

/**
 * Adapter for the GridFS filesystem on MongoDB database
 *
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class GridFS implements Adapter,
                        FileFactory,
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
    public function __construct(MongoGridFS $gridFS)
    {
        $this->gridFS = $gridFS;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $file = $this->find($key);

        return ($file) ? $file->getBytes() : false;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key)
    {
        $gridFSFile = $this->gridFS->findOne(array('filename' => $key));
        $file = new GridFSFile($gridFSFile->file['filename']);
        $file->setGridFSFile($gridFSFile);
        //Set data for file (do not set content, it's lazy)
        if (isset($gridFSFile->file['metadata'])) {
            $file->setMetadata($gridFSFile->file['metadata']);
        }
        $file->setName($gridFSFile->file['name']);
        $file->setTimestamp($gridFSFile->file['date']->sec);
        $file->setSize($gridFSFile->file['length']);
        $file->setChecksum($gridFSFile->file['md5']);
        //@todo: Mimetype

        return $file;
    }
    
    /**
     * {@inheritDoc}
     */
    public function write($key, $content, $metadata = null)
    {
        if ($this->exists($key)) {
            $this->delete($key);
        }
        //@todo: Parse human-readable name for file from key somehow because plain keys are usually ugly.
        $name = $key;
        $gridMetadata = array(
            'date' => new MongoDate(),
            'name' => $name,
            'metadata' => $metadata,
            'filename' => $key,
        );
        $id = $this->gridFS->storeBytes($content, $gridMetadata);
        $gridFSFile = $this->gridFS->findOne(array('_id' => $id));

        return $gridFSFile->getSize();
    }

    /**
     * {@inheritDoc}
     */    
    public function writeFile(File $file)
    {
        $key = $file->getKey();
        $gridMetadata = array(
            'date' => new MongoDate(),
            'name' => $file->getName(),
            'metadata' => $file->getMetadata(),
            'filename' => $key,
        );
        $id = $this->gridFS->storeBytes($file->getContent(), $gridMetadata);
        $gridFSFile = $this->gridFS->findOne(array('_id' => $id));
        if ($file instanceof GridFSFile) {
            $file->setGridFSFile($gridFSFile);
        }
        $file->setTimestamp($gridFSFile->file['date']->sec);
        $file->setSize($gridFSFile->file['length']);
        $file->setChecksum($gridFSFile->file['md5']);

        return true;
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
        $gridfsFile = $this->find($key, array('md5'));

        return ($gridfsFile) ? $gridfsFile->file['md5'] : false;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $gridfsFile = $this->find($key, array('_id'));

        return $gridfsFile && $this->gridFS->delete($gridfsFile->file['_id']);
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

    /**
     * Factory method for a new empty file object
     *
     * @param string $key
     * @param string $content
     *
     * @return Gaufrette\File\GridFS file
     */
    public function createFile($key, $content = null)
    {
        $f = new GridFSFile($key);
        if (isset($content)) {
            $f->setContent($content);
        }
        return $f;
    }

    /**
     * {@inheritDoc}
     */    
    public function isMetadataKeyAllowed($metaKey)
    {
        //GridFS accepts any metadata key
        return true;
    }
}