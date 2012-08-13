<?php

namespace Gaufrette\Adapter;

use Gaufrette\Exception;

/**
 * Amazon S3 adapter
 *
 * @package Gaufrette
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class AmazonS3 extends Base
{
    protected $service;
    protected $bucket;
    protected $ensureBucket = false;
    protected $create;
    protected $directory;

    public function __construct(\AmazonS3 $service, $bucket, $create = false)
    {
        $this->service = $service;
        $this->bucket = $bucket;
        $this->create = $create;
    }

    /**
     * Set the base directory the user will have access to
     *
     * @param  string $directory
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Get the directory the user has access to
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $this->ensureBucketExists();

        $response = $this->service->get_object(
            $this->bucket,
            $this->computePath($key)
        );

        if (404 === $response->status) {
            throw new Exception\FileNotFound($key);
        } elseif (!$response->isOK()) {
            throw new \RuntimeException(sprintf(
                'Could not read the "%s" file.',
                $key
            ));
        }

        return $response->body;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->ensureBucketExists();

        if ($this->exists($targetKey)) {
            throw new Exception\UnexpectedFile($targetKey);
        }

        $response = $this->service->copy_object(
            array( // source
                'bucket'   => $this->bucket,
                'filename' => $this->computePath($sourceKey)
            ),
            array( // target
                'bucket'   => $this->bucket,
                'filename' => $this->computePath($targetKey)
            )
        );

        if (404 === $response->status) {
            throw new Exception\FileNotFound($sourceKey);
        } elseif (!$response->isOK()) {
            throw new \RuntimeException(sprintf(
                'Could not rename the "%s" file into "%s".',
                $sourceKey,
                $targetKey
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $this->ensureBucketExists();
    
        $opt = array(
            'body' => $content,
            'acl'  => \AmazonS3::ACL_PUBLIC
        );
    
        $response = $this->service->create_object(
            $this->bucket,
            $this->computePath($key),
            $opt
        );

        if (!$response->isOK()) {
            throw new \RuntimeException(sprintf('Could not write the \'%s\' file.', $key));
        }

        return intval($response->header["x-aws-requestheaders"]["Content-Length"]);
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        $this->ensureBucketExists();

        return $this->service->if_object_exists(
            $this->bucket,
            $this->computePath($key)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        $response = $this->getObjectMetadata($key);

        return strtotime($response['Headers']['last-modified']);
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        $response = $this->getObjectMetadata($key);

        return trim($response['ETag'], '"');
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $this->ensureBucketExists();

        $response = $this->service->list_objects($this->bucket);
        if (!$response->isOK()) {
            throw new \RuntimeException('Could not get the keys.');
        }

        $keys = array();
        foreach ($response->body->Contents as $object) {
            $keys[] = $object->Key->to_string();
        }

        return $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->ensureBucketExists();

        if (!$this->exists($key)) {
            throw new Exception\FileNotFound($key);
        }

        $response = $this->service->delete_object(
            $this->bucket,
            $this->computePath($key)
        );

        if (!$response->isOK()) {
            throw new \RuntimeException(sprintf(
                'Could not delete the "%s" file.',
                $key
            ));
        }
    }

    private function getObjectMetadata($key)
    {
        $this->ensureBucketExists();

        $response = $this->service->get_object_metadata(
            $this->bucket,
            $this->computePath($key)
        );

        if (false === $response) {
            throw new Exception\FileNotFound($key);
        }

        return $response;
    }

    /**
     * Ensures the specified bucket exists. If the bucket does not exists
     * and the create parameter is set to true, it will try to create the
     * bucket
     *
     * @throws \RuntimeException if the bucket does not exists or could not be
     *                          created
     */
    private function ensureBucketExists()
    {
        if ($this->ensureBucket) {
            return;
        }

        if ($this->service->if_bucket_exists($this->bucket)) {
            return;
        }

        if (!$this->create) {
            throw new \RuntimeException(sprintf(
                'The configured bucket "%s" does not exist.',
                $this->bucket
            ));
        }

        // @todo make this region configurable
        $response = $this->service->create_bucket(
            $this->bucket,
            \AmazonS3::REGION_US_E1
        );

        if (!$response->isOK()) {
            throw new \RuntimeException(sprintf(
                'Failed to create the configured bucket "%s".',
                $this->bucket
            ));
        }

        $this->ensureBucket = true;
    }

    /**
     * Computes the path for the specified key taking the bucket in account
     *
     * @param  string $key The key for which to compute the path
     *
     * @return string
     */
    private function computePath($key)
    {
        if (null === $this->directory || '' === $this->directory) {
            return $key;
        }

        return sprintf('%s/%s', $this->directory, $key);
    }

    /**
     * Computes the key for the specified path
     *
     * @param  string $path for which to compute the key
     */
    private function computeKey($path)
    {
        if (null === $this->directory || '' === $this->directory) {
            return $path;
        }

        $prefix = sprintf('%s/', $this->directory);

        if (0 !== strpos($path, $prefix)) {
            throw new \InvalidArgumentException(sprintf(
                'The specified path "%s" is out of the directory "%s".',
                $path,
                $this->directory
            ));
        }

        return substr($path, strlen($prefix));
    }
}
