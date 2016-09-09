<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Adapter\ListKeysAware;

/**
 * Google Cloud Storage adapter using the Google Cloud Client Library for PHP
 * http://googlecloudplatform.github.io/google-cloud-php/
 *
 * @package Gaufrette
 * @author  Lech Buszczynski <lecho@phatcat.eu>
 */
class GoogleCloudClientStorage implements Adapter, MetadataSupporter, ResourceSupporter, ListKeysAware {

    protected $storageClient;
    protected $bucket;
    protected $bucketValidated;
    protected $options  = array();
    protected $metadata = array();
    protected $resource = array();

    /**
     * @param Google\Cloud\Storage\StorageClient    $service    Authenticated storage client class
     * @param string                                $bucketName Name of the bucket
     * @param array                                 $options    Options are: "directory" and "acl" (see https://cloud.google.com/storage/docs/access-control/lists)
     */
    public function __construct(\Google\Cloud\Storage\StorageClient $storageClient, $bucketName, $options = array())
    {
        $this->storageClient = $storageClient;
        $this->setBucket($bucketName);
        $this->options = array_replace_recursive(
            array(
                'directory' => '',
                'acl'       => array()
            ),
            $options
        );
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
    
    protected function computePath($key)
    {
        if (strlen($this->options['directory']))
        {
            if (strcmp(substr($this->options['directory'], -1), '/') == 0)
            {
                return $this->options['directory'].$key;
            }
            return $this->options['directory'].'/'.$key;
        }
        return $key;
    }
    
    protected function isBucket()
    {
        if ($this->bucketValidated === true)
        {
            return true;
        } elseif (!$this->bucket->exists()) {
            throw new \RuntimeException(sprintf('Bucket %s does not exist.', $this->bucket->name()));
        }
        $this->bucketValidated = true;
        return true;
    }
    
    public function setBucket($name)
    {
        $this->bucketValidated = null;
        $this->bucket = $this->storageClient->bucket($name);       
        $this->isBucket();
    }
    
    public function getBucket()
    {
        return $this->bucket;
    }
    
    /**
     * {@inheritdoc}
     */
    public function read($key)
    {   
        $this->isBucket();     
        $object = $this->bucket->object($this->computePath($key));
        $info = $object->info();
        $this->setResource($key, $info);
        return $object->downloadAsString();
    }
    
    /**
     * {@inheritdoc}
     */
    public function write($key, $content)
    {
        $this->isBucket();
        
        $options = array(
            'resumable'     => true,
            'name'          => $this->computePath($key),
            'metadata'      => $this->getMetadata($key),
        );

        $object = $this->bucket->upload(
            $content,
            $options
        );

        $acl = $object->acl();
        foreach ($this->options['acl'] as $k => $v)
        {
            $acl->add($k, $v);
        }
        $object->update(array('metadata' => $this->getMetadata($key)));
        
        $info = $object->info();
        $this->setResource($key, $info);        
        return $info['size'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function exists($key)
    {
        $this->isBucket();
        $object = $this->bucket->object($this->computePath($key));
        if ($object->exists())
        {
            return true;
        }
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function isDirectory($key)
    {
        if ($this->exists($key . '/'))
        {
            return true;
        }
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = null)
    {
        $this->isBucket();        
        $keys = array();        
        if ($prefix === null)
        {
            $prefix = $this->options['directory'];
        } else {
            $prefix = $this->computePath($prefix);
        }
        foreach ($this->bucket->objects(array('prefix' => $prefix)) as $e)
        {
            $keys[] = $e->name();
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
        $this->isBucket();
        $object = $object = $this->bucket->object($this->computePath($key));
        $info = $object->info();
        $this->setResource($key, $info);
        return strtotime($info['updated']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $object = $this->bucket->object($this->computePath($key));
        $object->delete();
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        # there is no support for rename currently in Google Cloud Client Library 0.7.1 - it will be added in v0.8 any time now
        return false;
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
    
    /**
     * {@inheritdoc}
     */
    public function setResource($key, $data)
    {
        $this->resource[$key] = $data;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getResource($key, $name = null)
    {
        if (isset($this->resource[$key]))
        {
            if ($name)
            {
                return isset($this->resource[$key][$name]) ? $this->resource[$key][$name] : null;
            } elseif (isset($this->resource[$key])) {
                return isset($this->resource[$key]) ? $this->resource[$key] : array();
            }
        }            
        return array();
    }    
}