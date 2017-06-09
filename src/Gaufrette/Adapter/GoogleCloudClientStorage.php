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
class GoogleCloudClientStorage implements Adapter, MetadataSupporter, ListKeysAware
{
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
    
    protected function computePath($key = null)
    {
        if (strlen($this->options['directory']))
        {
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
        try {
            $info = $object->info();
            $this->setResources($key, $info);
            return $object->downloadAsString();
        } catch (\Exception $e) {
            return false;
        }
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
        return $object->exists();
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
        $this->isBucket();        
        $keys = array();
        
        foreach ($this->bucket->objects(array('prefix' => $this->computePath($prefix))) as $e)
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
        try {
            $object = $this->bucket->object($this->computePath($key));
            $object->delete();
            $this->setMetadata($key, null);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->isBucket();
        
        $pathedSourceKey = $this->computePath($sourceKey);
        $pathedTargetKey = $this->computePath($targetKey);
                
        $object = $this->bucket->object($pathedSourceKey);
        
        $copiedObject = $object->copy($this->bucket,
            array(
                'name' => $pathedTargetKey
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
        $this->metadata[$this->computePath($key)] = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key)
    {
        $pathedKey = $this->computePath($key);
        if (!isset($this->metadata[$pathedKey]) && $this->exists($pathedKey))
        {
            $data = $this->bucket->object($pathedKey)->info();
            if (isset($data['metadata']))
            {
                $this->metadata[$pathedKey] = $data['metadata'];
            }
        }
        return isset($this->metadata[$pathedKey]) ? $this->metadata[$pathedKey] : array();
    }
    
    /**
     * {@inheritdoc}
     */
    public function setResources($key, $data)
    {
        $this->resources[$this->computePath($key)] = $data;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getResources($key)
    {
        $pathedKey = $this->computePath($key);
        return isset($this->resources[$pathedKey]) ? $this->resources[$pathedKey] : array();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getResourceByName($key, $resourceName)
    {
        $pathedKey = $this->computePath($key);
        return isset($this->resources[$pathedKey][$resourceName]) ? $this->resources[$pathedKey][$resourceName] : null;
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
