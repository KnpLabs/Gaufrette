<?php

namespace Gaufrette\Adapter;

use Gaufrette\Exception;

/**
 * Adapter for the GridFS filesystem on MongoDB database
 *
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class GridFS extends Base
{
    protected $gridFS = null;

    /**
     * Constructor
     *
     * @param \MongoGridFS $gridFS
     */
    public function __construct(\MongoGridFS $gridFS)
    {
        $this->gridFS = $gridFS;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        return $this->findOrError($key)->getBytes();
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        if ($this->exists($key)) {
            $this->delete($key);
        }

        $id   = $this->gridFS->storeBytes($content, array('filename' => $key, 'date' => new \MongoDate()));
        $file = $this->gridFS->findOne(array('_id' => $id));

        return $file->getSize();
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $file = $this->findOrError($sourceKey);

        if ($this->exists($targetKey)) {
            throw new Exception\UnexpectedFile($targetKey);
        }

        $this->write($targetKey, $file->getBytes());
        $this->delete($sourceKey);
    }

    /**
     * {@InheritDoc}
     */
    public function exists($key)
    {
        return null !== $this->gridFS->findOne($key);
    }

    /**
     * {@InheritDoc}
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
     * {@InheritDoc}
     */
    public function mtime($key)
    {
        return $this->findOrError($key, array('date'))->file['date']->sec;
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        return $this->findOrError($key, array('md5'))->file['md5'];
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $file = $this->findOrError($key, array('_id'));

        if (!$this->gridFS->delete($file->file['_id'])) {
            throw new \RuntimeException(sprintf(
                'Cannot delete file "%s" from the Mongo GridFS.',
                $key
            ));
        }
    }

    private function findOrError($key, array $fields = array())
    {
        $file = $this->find($key, $fields);

        if (null === $file) {
            throw new Exception\FileNotFound($key);
        }

        return $file;
    }

    private function find($key, array $fields = array())
    {
        return $this->gridFS->findOne($key, $fields);
    }
}
