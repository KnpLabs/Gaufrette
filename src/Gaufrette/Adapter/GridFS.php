<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use MongoDB\BSON\Regex;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;

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
    /** @var array */
    private $metadata = [];

    /** @var Bucket */
    private $bucket;

    /**
     * @param Bucket $bucket
     */
    public function __construct(Bucket $bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * {@inheritdoc}
     */
    public function read($key)
    {
        try {
            $stream = $this->bucket->openDownloadStreamByName($key);
        } catch (FileNotFoundException $e) {
            return false;
        }

        try {
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        $stream = $this->bucket->openUploadStream($key, ['metadata' => $this->getMetadata($key)]);

        try {
            return fwrite($stream, $content);
        } finally {
            fclose($stream);
        }

        return false;
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
        $metadata = $this->getMetadata($sourceKey);
        $writable = $this->bucket->openUploadStream($targetKey, ['metadata' => $metadata]);

        try {
            $this->bucket->downloadToStreamByName($sourceKey, $writable);
            $this->setMetadata($targetKey, $metadata);
            $this->delete($sourceKey);
        } catch (FileNotFoundException $e) {
            return false;
        } finally {
            fclose($writable);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        return (boolean) $this->bucket->findOne(['filename' => $key]);
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        $keys = [];
        $cursor = $this->bucket->find([], ['projection' => ['filename' => 1]]);

        foreach ($cursor as $file) {
            $keys[] = $file['filename'];
        }

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        $file = $this->bucket->findOne(['filename' => $key], ['projection' => ['uploadDate' => 1]]);

        return $file ? (int) $file['uploadDate']->toDateTime()->format('U') : false;
    }

    /**
     * {@inheritdoc}
     */
    public function checksum($key)
    {
        $file = $this->bucket->findOne(['filename' => $key], ['projection' => ['md5' => 1]]);

        return $file ? $file['md5'] : false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        if (null === $file = $this->bucket->findOne(['filename' => $key], ['projection' => ['_id' => 1]])) {
            return false;
        }

        $this->bucket->delete($file['_id']);

        return true;
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
        if (isset($this->metadata[$key])) {
            return $this->metadata[$key];
        } else {
            $meta = $this->bucket->findOne(['filename' => $key], ['projection' => ['metadata' => 1,'_id' => 0]]);
            if ($meta === null) {
                return array();
            }
            $this->metadata[$key] = iterator_to_array($meta['metadata']);
            return $this->metadata[$key];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = '')
    {
        $prefix = trim($prefix);

        if ($prefix === '') {
            return [
                'dirs' => [],
                'keys' => $this->keys(),
            ];
        }

        $regex = new Regex(sprintf('^%s', $prefix), '');
        $files = $this->bucket->find(['filename' => $regex], ['projection' => ['filename' => 1]]);
        $result = [
            'dirs' => [],
            'keys' => [],
        ];

        foreach ($files as $file) {
            $result['keys'][] = $file['filename'];
        }

        return $result;
    }
}
