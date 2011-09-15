<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Checksum;
use Gaufrette\Path;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Adapter for the GridFS filesystem on MongoDB database
 *
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 */
class GridFS implements Adapter
{
	//protected $gridfs; //MongoGridFS object
	protected static $gridfsInstances = array(); //Array of connections
	//Name of the instance for this adapter
	protected $instanceName = '';
	
    /**
     * Constructor
     *
     * @param string $serverUri for opening a new Mongo instance
     * @param string $databaseName Name of the database
     * @param string $collectionName Name of the collection in which the filesystem is located (equivalent for sql's tables)
     * @param array $options Additional options for initializing Mongo instance (see MongoDB documentation)
     */
    public function __construct($serverUri, $databaseName, $collectionName='', $options=array())
    {
    	//Generate instance name hash from all given parameters combined
    	$this->instanceName = md5(trim($serverUri).trim($databaseName).trim($collectionName));
    	//If instance already exists, no need to create a new one (request level performance)
    	if (array_key_exists($this->instanceName, self::$gridfsInstances))
    	{
    		return true;
    	}   
    	//Create a new GridFS instance 
    	$mongoInstance = new \Mongo($serverUri, $options);
    	$mongoDatabase = $mongoInstance->$databaseName;
        	
    	if (isset($collectionName) && strlen($collectionName) > 0)
    	{
	    	self::$gridfsInstances[$this->instanceName] = new \MongoGridFS($mongoDatabase, $collectionName);
    	}
    	else
    	{
	    	self::$gridfsInstances[$this->instanceName] = new \MongoGridFS($mongoDatabase);
    	}    	    	  	
    	return true;
    }

    /**
     * Gets file object by key
     * 
     * @param string $key
     * @return File file object
     */
    public function get($key, $filesystem)
    {
    	$gridfsFile = self::$gridfsInstances[$this->instanceName]->findOne(array('filename'=>$key));
    	$file = new File($key, $filesystem);
		$file->setMetadata($gridfsFile->file['metadata']);    	
    	return $file;
    }
    
    /**
     * {@InheritDoc}
     */
    public function read($key)
    {
    	//var_dump( Path::normalize($key));
    	$gridfsFile = self::$gridfsInstances[$this->instanceName]->findOne(array('filename'=>$key));
    	return $gridfsFile->getBytes(); 
    }

    /**
     * {@InheritDoc}
     */
    public function write($key, $content, $metadata=null)
    {    	
    	//Test if file already exists
    	if ($this->exists($key))
    	{
    		throw new \Exception("File already exists with key '$key'. Cannot write (delete first).");
    	}
    	
    	$mongoId = self::$gridfsInstances[$this->instanceName]->storeBytes($content, array('filename'=>$key,'metadata' => $metadata));    	

    	$numBytes = strlen($content); //TODO: How to count bytes from gridfs insetion

    	return $numBytes;
		//Would be better to return some kind of File Abstraction object    	     	
    }

    /**
     * {@InheritDoc}
     */
    public function rename($key, $new)
    {
    	//Rename = delete + write with a new name
		$file = $this->get($key);
    	$content  = $this->read($key);
    	return $this->write($key, $content, $file->getMetadata());
    }

    /**
     * {@InheritDoc}
     */
    public function exists($key)
    {
    	//Test if file already exists
    	return is_object(self::$gridfsInstances[$this->instanceName]->findOne(array('filename'=>$key)));
    }

    /**
     * {@InheritDoc}
     */
    public function keys()
    {
    	//NOT IMPLEMENTED
    	/*
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->directory,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::UNIX_PATHS
            )
        );

        $files = iterator_to_array($iterator);

        $self = $this;
        return array_values(
            array_map(
                function($file) use ($self) {
                    return $self->computeKey(strval($file));
                },
                $files
            )
        );
        */
    }

    /**
     * {@InheritDoc}
     */
    public function mtime($key)
    {
    	//NOT IMPLEMENTED YET    	
        //return filemtime($this->computePath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
    	//NOT IMPLEMENTED
        //return Checksum::fromFile($this->computePath($key));
    }

    /**
     * {@InheritDoc}
     */
    public function delete($key)
    {
    	//Test if file exists
    	if (! $this->exists($key))
       	{
    		throw new \Exception("File does not exists with key '$key'. Cannot remove.");
    	}
    	self::$gridfsInstances[$this->instanceName]->remove(array('filename'=>$key));
    	return true;    	
    }

    /**
     * Computes the path from the specified key
     *
     * @param  string $key The key which for to compute the path
     *
     * @return string A path
     *
     * @throws OutOfBoundsException If the computed path is out of the
     *                              directory
     */
    /*
    public function computePath($key)
    {
        $path = $this->normalizePath($this->directory . '/' . $key);

        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The file \'%s\' is out of the filesystem.', $key));
        }

        return $path;
    }
	*/
    /**
     * {@InheritDoc}
     */
    public function supportsMetadata()
    {
    	return true;	
    }
    
    
    /**
     * Normalizes the given path
     *
     * @param  string $path
     *
     * @return string
     */
    /*
    public function normalizePath($path)
    {
        return Path::normalize($path);
    }
    */

    /**
     * Computes the key from the specified path
     *
     * @param  string $path
     *
     * return string
     */
    /*
    public function computeKey($path)
    {
        $path = $this->normalizePath($path);
        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The path \'%s\' is out of the filesystem.', $path));
        }

        return ltrim(substr($path, strlen($this->directory)), '/');
    }
    */

    /**
     * Ensures the specified directory exists, creates it if it does not
     *
     * @param  string  $directory Path of the directory to test
     * @param  boolean $create    Whether to create the directory if it does
     *                            not exist
     *
     * @throws RuntimeException if the directory does not exists and could not
     *                          be created
     */
    /* TURHA?
    public function ensureDirectoryExists($directory, $create = false)
    {
        if (!is_dir($directory)) {
            if (!$create) {
                throw new \RuntimeException(sprintf('The directory \'%s\' does not exist.', $directory));
            }

            $this->createDirectory($directory);
        }
    }
	*/
    /**
     * Creates the specified directory and its parents
     *
     * @param  string $directory Path of the directory to create
     *
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    /* TURHA?
    public function createDirectory($directory)
    {
        if (is_dir($directory)) {
            throw new \InvalidArgumentException(sprintf('The directory \'%s\' already exists.', $directory));
        }

        $umask = umask(0);
        $created = mkdir($directory, 0777, true);
        umask($umask);

        if (!$created) {
            throw new \RuntimeException(sprintf('The directory \'%s\' could not be created.', $directory));
        }
    }
    */
}
