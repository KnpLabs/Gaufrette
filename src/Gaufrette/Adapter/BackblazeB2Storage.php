<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use B2Backblaze\B2Service as B2Service;
use B2Backblaze\B2Exception as B2Exception;

/**
 * Backblaze B2 Cloud Storage adapter using the B2Backblaze Client Library for PHP
 *
 * @package Gaufrette
 * @author  Kamil Zabdyr <kamilzabdyr@gmail.com>
 */
class BackblazeB2Storage implements Adapter
{
    protected $service;
    protected $bucket;
    protected $bucketName;
    protected $options;
    protected $bucketExists;
    protected $metadata = array();
    protected $detectContentType;

    /**
     * @param B2Service    $service       The storage service class with authenticatedclient and full access scope
     * @param string      $bucket        The bucket name
     * @param array       $options       Options can be directory and private
     */
    public function __construct(B2Service $service, $bucket, array $options = array()) {
        $this->service = $service;
        $this->bucket = $bucket;
        $this->options = array_replace(array(
            "directory" => "",
            "private" => false
        ), $options);
    }

    /**
     * @return array The actual options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options The new options
     */
    public function setOptions($options)
    {
        $this->options = array_replace($this->options, $options);
    }

    /**
     * @return string The current bucket name
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Sets a new bucket name.
     *
     * @param string $bucket The new bucket name
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * {@inheritdoc}
     */
    public function read($key)
    {
        $this->ensureBucketExists();
        try{
            $result =  $this->service->get($this->bucketName, $this->computePath($key),$this->options["private"]);
            if(!is_array($result)) return false;
            return $result["content"];
        }catch (B2Exception $e){
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        $this->ensureBucketExists();
        try {
            $object = $this->service->insert($this->bucket, $content, $this->computePath($key));
            if(!is_array($object)) return false;
            return $object["contentLength"];
        } catch (B2Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        $this->ensureBucketExists();
        try {
            return $this->service->exists($this->bucketName, $this->computePath($key));
        } catch (B2Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        $list = $this->service->all($this->bucket);

        $keys = array();
        foreach ($list as $file) {
            $keys[] = $file["fileName"];
        }
        sort($keys);

        return $keys;
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        $this->ensureBucketExists();

        $response = $this->service->get(
            $this->bucketName,
            $this->computePath($key),
            $this->options["private"],
            true
        );

        return isset($response['X-Bz-Info-src_last_modified_millis']) ? strtotime($response['X-Bz-Info-src_last_modified_millis']) : false;

    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $this->ensureBucketExists();

        try {
            return $this->service->delete($this->bucketName,  $this->computePath($key), $this->options["private"]);
        } catch (B2Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->ensureBucketExists();

        try {
            return $this->service->rename(
                $this->bucket,
                $this->bucketName,
                $this->computePath($sourceKey),
                $this->bucketName,
                $this->computePath($targetKey),
                $this->options["private"]);
        } catch (B2Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
    {
        if ($this->exists($key . '/')) {
            return true;
        }

        return false;
    }



    /**
     * Ensures the specified bucket exists.
     *
     * @throws \RuntimeException if the bucket does not exists
     */
    protected function ensureBucketExists()
    {
        if ($this->bucketExists) {
            return;
        }
        try {
            $bucket = $this->service->getBucketById($this->bucket);
            if($bucket == false){ throw new B2Exception("does not exist"); }
            $this->bucketExists = true;
            $this->bucketName = $bucket["bucketName"];

            return;
        } catch (B2Exception $e) {
            $this->bucketExists = false;

            throw new \RuntimeException(
                sprintf(
                    'The configured bucket "%s" does not exist.',
                    $this->bucket
                )
            );
        }
    }

    protected function computePath($key)
    {
        if (empty($this->options['directory'])) {
            return $key;
        }
        return sprintf('%s/%s', $this->options['directory'], $key);
    }
}
