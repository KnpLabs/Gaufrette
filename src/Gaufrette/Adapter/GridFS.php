<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\StorageFailure;
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
class GridFS implements Adapter, ChecksumCalculator, MetadataSupporter, ListKeysAware, SizeCalculator
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
            throw new FileNotFound($key);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('read', ['key' => $key], $e);
        }

        try {
            return stream_get_contents($stream);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('read', ['key' => $key], $e);
        } finally {
            fclose($stream);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        try {
            $stream = $this->bucket->openUploadStream($key, ['metadata' => $this->getMetadata($key)]);

            fwrite($stream, $content);
            fclose($stream);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('write', ['key' => $key], $e);
        }
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

        try {
            $writable = $this->bucket->openUploadStream($targetKey, ['metadata' => $metadata]);
            $this->bucket->downloadToStreamByName($sourceKey, $writable);

            $this->setMetadata($targetKey, $metadata);
            $this->delete($sourceKey);

            fclose($writable);
        } catch (FileNotFoundException $e) {
            throw new FileNotFound($sourceKey);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('rename', ['sourceKey' => $sourceKey, 'targetKey' => $targetKey], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        try {
            return $this->bucket->findOne(['filename' => $key]) !== null;
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('exists', ['key' => $key], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        $keys = [];

        try {
            $cursor = $this->bucket->find([], ['projection' => ['filename' => 1]]);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('keys', [], $e);
        }

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
        try {
            $file = $this->bucket->findOne(['filename' => $key], ['projection' => ['uploadDate' => 1]]);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('mtime', ['key' => $key], $e);
        }

        if ($file === null) {
            throw new FileNotFound($key);
        }

        return (int) $file['uploadDate']->toDateTime()->format('U');
    }

    /**
     * {@inheritdoc}
     */
    public function checksum($key)
    {
        try {
            $file = $this->bucket->findOne(['filename' => $key], ['projection' => ['md5' => 1]]);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('checksum', ['key' => $key], $e);
        }

        if ($file === null) {
            throw new FileNotFound($key);
        }

        return $file['md5'];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        try {
            $file = $this->bucket->findOne(['filename' => $key], ['projection' => ['_id' => 1]]);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('delete', ['key' => $key], $e);
        }

        if ($file === null) {
            throw new FileNotFound($key);
        }

        try {
            $this->bucket->delete($file['_id']);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('delete', ['key' => $key], $e);
        }
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
        }
        $meta = $this->bucket->findOne(['filename' => $key], ['projection' => ['metadata' => 1,'_id' => 0]]);

        if ($meta === null || !isset($meta['metadata'])) {
            return [];
        }

        $this->metadata[$key] = iterator_to_array($meta['metadata']);

        return $this->metadata[$key];
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

        try {
            $files = $this->bucket->find(['filename' => $regex], ['projection' => ['filename' => 1]]);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('listKeys', ['prefix' => $prefix]);
        }

        $result = [
            'dirs' => [],
            'keys' => [],
        ];

        foreach ($files as $file) {
            $result['keys'][] = $file['filename'];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function size($key)
    {
        if (!$this->exists($key)) {
            throw new FileNotFound($key);
        }

        $size = $this->bucket->findOne(['filename' => $key], ['projection' => ['length' => 1,'_id' => 0]]);

        if (!isset($size['length'])) {
            throw StorageFailure::unexpectedFailure('size', ['key' => $key]);
        }

        return $size['length'];
    }
}
