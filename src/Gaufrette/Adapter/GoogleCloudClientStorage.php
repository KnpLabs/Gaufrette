<?php
namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Adapter\ListKeysAware;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\StorageFailure;
use Google\Cloud\Exception\NotFoundException;
use Google\Cloud\Storage\Bucket;
use Google\Cloud\Storage\StorageClient;
use Google\Cloud\Storage\StorageObject;

/**
 * Google Cloud Storage adapter using the Google Cloud Client Library for PHP
 * http://googlecloudplatform.github.io/google-cloud-php/
 *
 * @package Gaufrette
 * @author  Lech Buszczynski <lecho@phatcat.eu>
 */
final class GoogleCloudClientStorage implements Adapter, MetadataSupporter, ListKeysAware
{
    /**
     * @var StorageClient
     */
    private $storageClient;

    /**
     * @var Bucket
     */
    private $bucket;
    private $options      = array();
    private $metadata     = array();

    /**
     * @param StorageClient    $service    Authenticated storage client class
     * @param string           $bucketName Name of the bucket
     * @param array            $options    Options are: "directory" and "acl" (see https://cloud.google.com/storage/docs/access-control/lists)
     */
    public function __construct(StorageClient $storageClient, $bucketName, $options = array())
    {
        $this->storageClient = $storageClient;
        $this->initBucket($bucketName);
        $this->options = array_replace_recursive(
            array(
                'directory' => '',
                'acl'       => array(),
            ),
            $options
        );
        $this->options['directory'] = rtrim($this->options['directory'], '/');
    }

    /**
     * Get adapter options
     *
     * @return  array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set adapter options
     *
     * @param   array   $options
     */
    public function setOptions($options)
    {
        $this->options = array_replace($this->options, $options);
    }

    /**
     * @return Bucket
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * {@inheritdoc}
     */
    public function read($key)
    {
        $object = $this->bucket->object($this->computePath($key));

        try {
            return $object->downloadAsString();
        } catch (\Exception $e) {
            if ($e instanceof NotFoundException) {
                throw new FileNotFound($key);
            }

            throw StorageFailure::unexpectedFailure('read', ['key' => $key], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        $options = array(
            'resumable'     => true,
            'name'          => $this->computePath($key),
        );

        try {
            $object = $this->bucket->upload(
                $content,
                $options
            );

            $this->setAcl($object);
        } catch (\Exception $e) {
            throw StorageFailure::unexpectedFailure('write', ['key' => $key, 'content' => $content], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        return $this->bucket->object($this->computePath($key))->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
    {
        return $this->exists($this->computePath(rtrim($key, '/')).'/');
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = null)
    {
        // @FIXME : the list should not return the root directory in the keys
        $keys = array();

        foreach ($this->bucket->objects(array('prefix' => $this->computePath($prefix))) as $e) {
            $keys[] = strlen($this->options['directory'])
                ? substr($e->name(), strlen($this->options['directory'] . '/'))
                : $e->name()
            ;
        }

        sort($keys);

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        return $this->listKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        $info = $this->bucket->object($this->computePath($key))->info();

        return strtotime($info['updated']);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        try {
            $this->bucket->object($this->computePath($key))->delete();
        } catch (\Exception $e) {
            if ($e instanceof NotFoundException) {
                throw new FileNotFound($key);
            }

            throw StorageFailure::unexpectedFailure('delete', ['key' => $key], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $pathedSourceKey = $this->computePath($sourceKey);
        $pathedTargetKey = $this->computePath($targetKey);

        try {
            $object = $this->bucket->object($pathedSourceKey);
            $metadata = $this->getMetadata($sourceKey);

            $copy = $object->copy($this->bucket,
                array(
                    'name' => $pathedTargetKey
                )
            );

            $this->setAcl($copy);
            $this->setMetadata($targetKey, $metadata);

            $object->delete();
        } catch (\Exception $e) {
            if ($e instanceof NotFoundException) {
                throw new FileNotFound($key);
            }

            throw StorageFailure::unexpectedFailure('rename', ['sourceKey' => $sourceKey, 'targetKey' => $targetKey], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key)
    {
        try {
            $infos = $this->bucket->object($this->computePath($key))->info();

            return isset($infos['metadata'])
                ? $infos['metadata']
                : []
            ;
        } catch (\Exception $e) {
            if ($e instanceof NotFoundException) {
                throw new FileNotFound($key);
            }

            throw StorageFailure::unexpectedFailure('getMetadata', ['key' => $key], $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata($key, $metadata)
    {
        try {
            $this->bucket->object($this->computePath($key))->update(array('metadata' => $metadata));
        } catch (\Exception $e) {
            if ($e instanceof NotFoundException) {
                throw new FileNotFound($key);
            }

            throw StorageFailure::unexpectedFailure('setMetadata', ['key' => $key], $e);
        }
    }

    private function computePath($key = null)
    {
        if (strlen($this->options['directory'])) {
            return $this->options['directory'].'/'.$key;
        }

        return $key;
    }

    private function initBucket($bucketName)
    {
        $this->bucket = $this->storageClient->bucket($bucketName);

        if (!$this->bucket->exists()) {
            throw new StorageFailure(sprintf('Bucket %s does not exist.', $bucketName));
        }
    }

    /**
     * Set the ACLs received in the options (if any) to the given $object.
     *
     * @param StorageObject $object
     */
    private function setAcl(StorageObject $object)
    {
        if (!isset($this->options['acl']) || empty($this->options['acl'])) {
            return;
        }

        $acl = $object->acl();

        foreach ($this->options['acl'] as $key => $value) {
            $acl->add($key, $value);
        }
    }
}
