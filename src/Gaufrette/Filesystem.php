<?php
namespace Gaufrette;

/**
 * A filesystem is used to store and retrieve files
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 */
interface Filesystem
{

    /**
     * Indicates whether the file matching the specified key exists
     *
     * @param string $key
     *
     * @return boolean TRUE if the file exists, FALSE otherwise
     */
    function exists($key);

    /**
     * Returns the file object matching the specified key
     *
     * @param string  $key    Key of the file
     * @param boolean $create Whether to create the file if it does not exist
     *
     * @throws Gaufrette\Exception\FileNotFound
     * @return File
     */
    function read($key);    
    
    /**
     * Writes a complete file object into storage
     * 
     * @param File $file File object with a valid key and content
     * @throws \InvalidArgumentException when key or content are not set for file
     * 
     * @return File $file
     */
    function write(File $file);
    
    /**
     * Deletes the file matching the specified key
     *
     * @param string $key
     *
     * @return boolean
     */
    function delete($key);

    /**
     * Renames a file
     *
     * @param string $sourceKey
     * @param string $targetKey
     *
     * @return boolean                  TRUE if the rename was successful
     * @throws Exception\FileNotFound   when sourceKey does not exist
     * @throws Exception\UnexpectedFile when targetKey exists
     * @throws \RuntimeException        when cannot rename
     */
    function rename($sourceKey, $targetKey);    
    
    /**
     * Returns an array of all keys
     *
     * @return array
     */
    function keys();

    /**
     * Factory method for a new empty file object
     *
     * @param string key
     *
     * @param File file
     */
    public function createFile($key);    
        
    /**
     * Returns an array of all items (files and directories) matching the specified pattern
     *
     * @param  string $pattern
     * @return array
     */
    /*
    public function listKeys($pattern = '')
    {
        if ($this->adapter instanceof ListKeysAware) {
            return $this->adapter->listKeys($pattern);
        }

        $dirs = array();
        $keys = array();

        foreach ($this->keys() as $key) {
            if (empty($pattern) || false !== strpos($key, $pattern)) {
                if ($this->adapter->isDirectory($key)) {
                    $dirs[] = $key;
                } else {
                    $keys[] = $key;
                }
            }
        }

        return array(
            'keys' => $keys,
            'dirs' => $dirs
        );
    }
    */




}
