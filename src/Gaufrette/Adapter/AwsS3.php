<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Aws\S3\S3Client;

/**
 * Amazon S3 adapter using the AWS SDK for PHP v2.x
 *
 * @package Gaufrette
 * @author  Michael Dowling <mtdowling@gmail.com>
 */
class AwsS3 implements Adapter,
                       MetadataSupporter,
                       ListKeysAware
{
    protected $service;
    protected $bucket;
    protected $options;
    protected $bucketExists;
    protected $metadata = array();

    public function __construct(S3Client $service, $bucket, array $options = array())
    {
        $this->service = $service;
        $this->bucket = $bucket;
        $this->options = array_replace(array('create' => false), $options);
    }

    /**
     * Gets the publicly accessible URL of an Amazon S3 object
     *
     * @param string $key     Object key
     * @param array  $options Associative array of options used to buld the URL
     *                       - expires: The time at which the URL should expire
     *                           represented as a UNIX timestamp
     *                       - Any options available in the Amazon S3 GetObject
     *                           operation may be specified.
     * @return string
     */
    public function getUrl($key, array $options = array())
    {
        return $this->service->getObjectUrl(
            $this->bucket,
            $key,
            isset($options['expires']) ? $options['expires'] : null,
            $options
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setMetadata($key, $metadata)
    {
        $this->metadata[$key] = $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata($key)
    {
        return isset($this->metadata[$key]) ? $this->metadata[$key] : array();
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $this->ensureBucketExists($key);
        $options = $this->getOptions($key);

        try {
            return (string) $this->service->getObject($options)->get('Body');
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->ensureBucketExists($targetKey);
        $options = $this->getOptions($targetKey, array('CopySource' => $sourceKey));

        try {
            $this->service->copyObject($options);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content)
    {
        $this->ensureBucketExists($key);
        $options = $this->getOptions($key, array('Body' => $content));

        try {
            $this->service->putObject($options);
            return strlen($content);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return $this->service->doesObjectExist($this->bucket, $key);
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        try {
            $result = $this->service->headObject($this->getOptions($key));
            return strtotime($result['LastModified']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        return $this->listKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = '')
    {
        $options = array('Bucket' => $this->bucket);
        if ((string) $prefix != '') {
            $options['Prefix'] = $prefix;
        }

        $keys = array();
        $iter = $this->service->getIterator('ListObjects', $options);
        foreach ($iter as $file) {
            $keys[] = $file['Key'];
        }

        return $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        try {
            $this->service->deleteObject($this->getOptions($key));
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isDirectory($key)
    {
        $result = $this->service->listObjects(array(
            'Bucket'  => $this->bucket,
            'Prefix'  => rtrim($key, '/') . '/',
            'MaxKeys' => 1
        ));

        return count($result['Contents']) > 0;
    }

    /**
     * Ensures the specified bucket exists. If the bucket does not exists
     * and the create option is set to true, it will try to create the
     * bucket. The bucket is created using the same region as the supplied
     * client object.
     *
     * @throws \RuntimeException if the bucket does not exists or could not be
     *                          created
     */
    private function ensureBucketExists()
    {
        if ($this->bucketExists) {
            return true;
        }

        if ($this->bucketExists = $this->service->doesBucketExist($this->bucket)) {
            return true;
        }

        if (!$this->options['create']) {
            throw new \RuntimeException(sprintf(
                'The configured bucket "%s" does not exist.',
                $this->bucket
            ));
        }

        $options = array('Bucket' => $this->bucket);
        if ($this->service->getRegion() != 'us-east-1') {
            $options['LocationConstraint'] = $this->service->getRegion();
        }

        $this->service->createBucket($options);
        $this->bucketExists = true;

        return true;
    }

    private function getOptions($key, array $options = array())
    {
        $options['Bucket'] = $this->bucket;
        $options['Key'] = $key;

        return $options + $this->getMetadata($key);
    }
}
