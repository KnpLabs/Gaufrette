<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Checksum;
use Gaufrette\Path;
use Gaufrette\File;

use Gaufrette\FileCursor\GridFS as GridFSFileCursor;
use Gaufrette\Filesystem;

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
        //Use specified collection or default collection
        if (isset($collectionName) && strlen($collectionName) > 0) {
            self::$gridfsInstances[$this->instanceName] = new \MongoGridFS($mongoDatabase, $collectionName);
        } else {
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
        $gridfsFile = self::$gridfsInstances[$this->instanceName]->findOne(array('key'=>$key));
        $file = new File($key, $filesystem);
        $file->setMetadata($gridfsFile->file['metadata']);
        $file->setName($gridfsFile->file['filename']);
        $file->setCreated($gridfsFile->file['uploadDate']->sec);
        $file->setSize($gridfsFile->file['length']);
        return $file;
    }

    /**
     * {@InheritDoc}
     */
    public function read($key)
    {
        //TODO: Normalize key somehow
        //var_dump( Path::normalize($key));
        $gridfsFile = self::$gridfsInstances[$this->instanceName]->findOne(array('key'=>$key));
        return $gridfsFile->getBytes();
    }

    /**
     * {@InheritDoc}
     * @param array metadata any metadata in assoc array format
     * @param string filename human readable (e.g. someImage.jpg) NOT IN USE ATM.
     */
    public function write($key, $content, $metadata=null)
    {
        //Test if file already exists
        if ($this->exists($key)) {
            throw new \Exception("File already exists with key '$key'. Cannot write (delete first).");
        }
        //Break down key, assume '/' is used for delimiter and last part is the filename
        $keyParts = array_filter(explode('/', $key));
        //Prepare data array
        $dataArray = array(
            'key' => $key,
            'filename' => $keyParts[count($keyParts)],
            'uploadDate' => new \MongoDate(),
            'metadata' => $metadata,
        );
        //Store
        $mongoId = self::$gridfsInstances[$this->instanceName]->storeBytes($content, $dataArray);
        //TODO: How to do better counting of bytes for gridfs insertion
        $numBytes = strlen($content);
        return $numBytes;
    }

    /**
     * Rename = fetch old + write new + delete old
     * {@InheritDoc}
     */
    public function rename($key, $new)
    {
        //Fetch file
        $file = $this->get($key);
        //Read content
        $content  = $this->read($key);
        //Write a new file
        $returnValue = $this->write($new, $content, $file->getMetadata());
        //Delete old file
        $this->delete($key);
        return $returnValue;
    }

    /**
     * {@InheritDoc}
     */
    public function exists($key)
    {
        //Test if file already exists
        return is_object(self::$gridfsInstances[$this->instanceName]->findOne(array('key'=>$key)));
    }

    /**
     * Query a group of files using partial key
     *
     * @param string keyFragment partial key from the beginning of the key
     * @param Filesystem filesystem object
     * @param string sortKey defines the variable that is used for sorting. Alternatives: 'name', 'created' or 'size'
     * @param string sortDirection. Alternatives: 'asc' or 'desc'
     * @return Iterator for File objects (can be array or anything that implements Iterator interface)
     */
    public function query($keyFragment, $filesystem, $sortKey='name', $sortDirection='asc')
    {
        $regex = new \MongoRegex("/^".$keyFragment."/");
        $gridfsCursor = self::$gridfsInstances[$this->instanceName]->find(array('key'=>$regex));

        //Sort cursor
        if ($sortDirection == 'asc') {
            $direction = 1;
        } elseif($sortDirection == 'desc') {
            $direction = -1;
        } else {
            throw new \LogicException("Invalid value for sortDirection. Must be 'asc' or 'desc'.");
        }
        if ($sortDirection == 'desc') {
            $direction = -1;
        }

        switch($sortKey)
        {
            case 'size':
                $gridfsCursor->sort(array('length' => $direction));
                break;
            case 'created':
                $gridfsCursor->sort(array('uploadDate' => $direction));
                break;
            case 'name':
                $gridfsCursor->sort(array('filename' => $direction));
                break;
            default:
                throw new \LogicException("Invalid sortKey argument for find. Must be 'created', 'name' or 'size'.");
                break;
        }

        //Return as a FileCursor (not prepared array) for lesser memory consumption
        return new GridFSFileCursor($gridfsCursor, $filesystem);
    }


    /**
     * {@InheritDoc}
     */
    public function keys()
    {
        /**
         * This seems to work but performance is a big question...
         */
        $cursor = self::$gridfsInstances[$this->instanceName]->find(array(), array('key'));
        $temp = array();
        foreach($cursor as $f)
        {
            $temp[] = $f->file['key'];
        }
        return $temp;
    }

    /**
     * NOT IMPLEMENTED YET
     * {@InheritDoc}
     */
    public function mtime($key)
    {
        //NOT IMPLEMENTED YET
        //return filemtime($this->computePath($key));
    }

    /**
     * NOT IMPLEMENTED YET
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
        if (! $this->exists($key)) {
            throw new \Exception("File does not exists with key '$key'. Cannot remove.");
        }
        self::$gridfsInstances[$this->instanceName]->remove(array('key'=>$key));
        return true;
    }

    /**
     * {@InheritDoc}
     */
    public function supportsMetadata()
    {
        return true;
    }


}