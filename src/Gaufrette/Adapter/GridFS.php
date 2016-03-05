<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use MongoGridFS as MongoGridFs;
use MongoDate;

/**
 * Adapter for the GridFS filesystem on MongoDB database.
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
    private $metadata = array();
    protected $gridFS = null;

    /**
     * @param \MongoGridFS $gridFS
     */
    public function __construct(MongoGridFs $gridFS)
    {
        $this->gridFS = $gridFS;
    }

    /**
     * {@inheritdoc}
     */
    public function read($key)
    {
        $file = $this->find($key);

        return ($file) ? $file->getBytes() : false;
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        if ($this->exists($key)) {
            $this->delete($key);
        }

        $metadata = array_replace_recursive(array('date' => new MongoDate()), $this->getMetadata($key), array('filename' => $key));
        $id = $this->gridFS->storeBytes($content, $metadata);
        $file = $this->gridFS->findOne(array('_id' => $id));

        return $file->getSize();
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $bytes = $this->write($targetKey, $this->read($sourceKey));
        $this->delete($sourceKey);

        return (boolean) $bytes;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        return (boolean) $this->find($key);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        $keys = array();
        $cursor = $this->gridFS->find(array(), array('filename'));

        foreach ($cursor as $file) {
            $keys[] = $file->getFilename();
        }

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        $file = $this->find($key, array('date'));

        return ($file) ? $file->file['date']->sec : false;
    }

    /**
     * {@inheritdoc}
     */
    public function checksum($key)
    {
        $file = $this->find($key, array('md5'));

        return ($file) ? $file->file['md5'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $file = $this->find($key, array('_id'));

        return $file && $this->gridFS->delete($file->file['_id']);
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata($key, $metadata)
    {
        $this->metadata[$key] = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key)
    {
        return isset($this->metadata[$key]) ? $this->metadata[$key] : array();
    }

    private function find($key, array $fields = array())
    {
        return $this->gridFS->findOne($key, $fields);
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = '')
    {
        $prefix = trim($prefix);

        if ('' == $prefix) {
            return array(
                'dirs' => array(),
                'keys' => $this->keys(),
            );
        }

        $result = array(
            'dirs' => array(),
            'keys' => array(),
        );

        $gridFiles = $this->gridFS->find(array(
            'filename' => new \MongoRegex(sprintf('/^%s/', $prefix)),
        ));

        foreach ($gridFiles as $file) {
            $result['keys'][] = $file->getFilename();
        }

        return $result;
    }
}
