<?php

namespace Gaufrette\Filesystem\Adapter;

use Gaufrette\Filesystem\Adapter;
use Zend\Service\Amazon\S3\S3;

/**
 * Amazon S3 adapter
 *
 * @package Gaufrette
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class AmazonS3 implements Adapter
{
    protected $service;
    protected $bucket;

    public function __construct(S3 $service, $bucket, $create = false)
    {
        $this->service = $service;
        $this->bucket = $bucket;
        $this->ensureBucketExists($bucket, $create);
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        return $this->service->getObject($this->computePath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content)
    {
        if ($this->service->putObject($this->computePath($key), $content)) {
            return $this->getStringNumBytes($content);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return $this->service->isObjectAvailable($this->computePath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        $info = $this->service->getInfo($this->computePath($key));

        return $info['mtime'];
    }

    /**
     * {@inheritDoc}
     */
    public function keys($pattern = null)
    {
        $matches = array();
        $objects = $this->service->getObjectsByBucket($this->bucket);

        if (null !== $pattern) {
            $objects = array_filter($objects, function($key) use($pattern) {
                return 0 === strpos($key, $pattern);
            });
        }

        return $objects;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        return $this->removeObject($this->computePath($key));
    }

    /**
     * Ensures the specified bucket exists. If the bucket does not exists
     * and the create parameter is set to true, it will try to create the
     * bucket
     *
     * @param  string  $bucket The name of the bucket
     * @param  boolean $create Whether to create the bucket
     *
     * @throws RuntimeException if the bucket does not exists or could not be
     *                          created
     */
    protected function ensureBucketExists($bucket, $create = false)
    {
        if ($this->service->isBucketAvailable($bucket)) {
            return;
        }

        if ($create) {
            $created = $this->service->createBucket($bucket);
            if (!$created) {
                throw new \RuntimeException(sprintf('Could not create the \'%s\' bucket.', $bucket));
            }
        } else {
            throw new \RuntimeException(sprintf('The bucket \'%s\' was not found.', $bucket));
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
     * Returns the number of bytes of the given string
     *
     * @param  string $string
     *
     * @return integer
     */
    protected function getStringNumBytes($string)
    {
        $d = 0;
        $strlen_var = strlen($str);
        for ($c = 0; $c < $strlen_var; ++$c) {

            $ord_var_c = ord($str{$d});

            switch (true) {
                case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                    $d++;
                    break;
                case (($ord_var_c & 0xE0) == 0xC0):
                    $d+=2;
                    break;
                case (($ord_var_c & 0xF0) == 0xE0):
                    $d+=3;
                    break;
                case (($ord_var_c & 0xF8) == 0xF0):
                    $d+=4;
                    break;
                case (($ord_var_c & 0xFC) == 0xF8):
                    $d+=5;
                    break;
                case (($ord_var_c & 0xFE) == 0xFC):
                    $d+=6;
                    break;
                default:
                    $d++;
            }
        }

        return $d;
    }
}
