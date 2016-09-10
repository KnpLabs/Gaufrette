<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Adapter\ResourcesSupporter;
use Gaufrette\Adapter\ListKeysAware;

/**
 * Google Cloud Storage adapter using the Google Cloud Client Library for PHP
 * http://googlecloudplatform.github.io/google-cloud-php/
 *
 * @package Gaufrette
 * @author  Lech Buszczynski <lecho@phatcat.eu>
 */
class GoogleCloudClientStorage implements Adapter, MetadataSupporter, ResourcesSupporter, ListKeysAware {

    protected $storageClient;
    protected $bucket;
    protected $bucketValidated;
    protected $options      = array();
    protected $metadata     = array();
    protected $resources    = array();

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
        $this->setResources($key, $info);
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

        $this->bucket->upload(
            $content,
            $options
        );
        
        $this->updateKeyProperties($key,
            array(
                'acl'       => $this->options['acl'],
                'metadata'  => $this->getMetadata($key)
            )
        );
        
        $size = $this->getResourceByName($key, 'size');
        
        return $size === null ? false : $size;
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
        $this->setResources($key, $info);
        return strtotime($info['updated']);
    }
    
    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $this->isBucket();
        $object = $this->bucket->object($this->computePath($key));
        $object->delete();
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->isBucket();
        
        $metadata = $this->getMetadata($sourceKey);
        
        $sourceKey = $this->computePath($sourceKey);
        $targetKey = $this->computePath($targetKey);
        
        $object = $this->bucket->object($sourceKey);
        
        $copiedObject = $object->copy($this->bucket,
            array(
                'name' => $targetKey
            )
        );
        
        if ($copiedObject->exists())
        {
            $this->updateKeyProperties($targetKey,
                array(
                    'acl'       => $this->options['acl'],
                    'metadata'  => $this->getMetadata($sourceKey)
                )
            );
            $this->setMetadata($targetKey, $this->getMetadata($sourceKey));
            $this->setMetadata($sourceKey, null);
            $object->delete();
            return true;
        }
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
    public function setResources($key, $data)
    {
        $this->resources[$key] = $data;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getResources($key)
    {
        return isset($this->resources[$key]) ? $this->resources[$key] : array();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getResourceByName($key, $resourceName)
    {
        return isset($this->resources[$key][$resourceName]) ? $this->resources[$key][$resourceName] : null;
    }
    
    /**
     * Sets ACL and metadata information.
     * 
     * @param   string  $key
     * @param   array   $options    Can contain "acl" and/or "metadata" arrays.
     * @return  boolean
     */
    protected function updateKeyProperties($key, $options = array())
    {
        if ($this->exists($key) === false)
        {
            return false;
        }
        
        $object = $this->bucket->object($this->computePath($key));
        
        $properties = array_replace_recursive(
            array(
                'acl'       => array(),
                'metadata'  => array()
            ), $options
        );

        $acl = $object->acl();
        foreach ($properties['acl'] as $k => $v)
        {
            $acl->add($k, $v);
        }
        $object->update(array('metadata' => $properties['metadata']));

        $info = $object->info();

        $this->setResources($key, $info);
        return true;
    }
}