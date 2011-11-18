<?php

namespace Gaufrette\Adapter;

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

    public function __construct(\AmazonS3 $service, $bucket, $create = false)
    {
        $this->service = $service;
        $this->bucket = $bucket;
        $this->create = $create;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $this->ensureBucketExists();

        $response = $this->service->get_object($this->bucket, $key);
        if (!$response->isOK()) {
            throw new \RuntimeException(sprintf('Could not read the \'%s\' file.', $key));
        }

        return $response->body;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($key, $new)
    {
        $source = array(
            "bucket" => $this->bucket,
            "filename" => $key,
        );

        $destination = array(
            "bucket" => $this->bucket,
            "filename" => $new,
        );

        $response = $this->service->copy_object($source, $destination);
        if (!$response->isOK()) {
            throw new \RuntimeException(sprintf('Could not rename the \'%s\' file.', $key));
        }

        $this->delete($key);
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $this->ensureBucketExists();

        $opt = array("body" => $content);

        if (null !== $metadata) {
            $opt['meta'] = array();
            foreach ($metadata as $k => $v) {
                $lk = strtolower($k);

                if ('content-type' === $lk) {
                    $opt['contentType'] = $v;
                    continue;
                }

                if ('expires' === $lk) {
                    $opt['headers']['Expires'] = $v;
                    continue;
                }

                if ('content-encoding' === $lk) {
                    $opt['headers']['Content-Encoding'] = $v;
                    continue;
                }

                $opt['meta'][$k] = $v;
            }
        }

        $response = $this->service->create_object($this->bucket, $key, $opt);
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

        return $this->service->if_object_exists($this->bucket, $key);
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        $headers = $this->getHeaders($key);

        return strtotime($headers['Last-modified']);
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        $headers = $this->getHeaders($key);

        return strtotime($headers['etag']);
    }

    /**
     * Fetch the headers of an object
     *
     * @param type $key Object of which to get the headers
     * @return type array Object headers
     */
    protected function getHeaders($key)
    {
        $this->ensureBucketExists();
        $response = $this->service->get_object_metadata($this->bucket, $key);

        if ($response === false) {
            throw new \RuntimeException(sprintf('The \'%s\' file does not exist.', $key));
        }

        return $response["Headers"];
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $this->ensureBucketExists();

        $response = $this->service->list_objects($this->bucket);
        if (!$response->isOK()) {
            throw new \RuntimeException(sprintf('Could not get the keys.', $key));
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

        $response = $this->service->delete_object($this->bucket, $key);
        if (!$response->isOK()) {
            throw new \RuntimeException(sprintf('Could not delete the \'%s\' file.', $key));
        }
    }

    /**
     * Ensures the specified bucket exists. If the bucket does not exists
     * and the create parameter is set to true, it will try to create the
     * bucket
     *
     * @throws RuntimeException if the bucket does not exists or could not be
     *                          created
     */
    protected function ensureBucketExists()
    {
        if (!$this->ensureBucket) {
            $available = $this->service->if_bucket_exists($this->bucket);

            if (!$available && $this->create) {
                $response = $this->service->createBucket($this->bucket, \AmazonS3::REGION_US_E1);
                $created = $response->isOK();
                if (!$created) {
                    throw new \RuntimeException(sprintf('Could not create the \'%s\' bucket.', $this->bucket));
                }
            } else if (!$available) {
                throw new \RuntimeException(sprintf('The bucket \'%s\' was not found. Please create it on Amazon AWS.', $this->bucket));
            }

            $this->ensureBucket = true;
        }
    }

    /**
     * Computes the path for the specified key taking the bucket in account
     *
     * @param  string $key The key for which to compute the path
     *
     * @return string
     */
    public function computePath($key)
    {
        return $this->bucket . '/' . $key;
    }

    /**
     * Computes the key for the specified path
     *
     * @param  string $path for which to compute the key
     */
    public function computeKey($path)
    {
        if (0 !== strpos($path, $this->bucket . '/')) {
            throw new \InvalidArgumentException(sprintf('The specified path \'%s\' is out of the bucket \'%s\'.', $path, $this->bucket));
        }

        return ltrim(substr($path, strlen($this->bucket)), '/');
    }

    /**
     * {@InheritDoc}
     */
    public function supportsMetadata()
    {
        return false;
    }
}
