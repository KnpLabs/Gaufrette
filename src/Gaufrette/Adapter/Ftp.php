<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\File;
use Gaufrette\Filesystem;
use Gaufrette\Exception;

/**
 * Ftp adapter
 *
 * @package Gaufrette
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class Ftp implements Adapter,
                     FileFactory
{
    protected $connection = null;
    protected $directory;
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $passive;
    protected $create;
    protected $mode;
    protected $fileData = array();

    /**
     * Constructor
     *
     * @param string $directory The directory to use in the ftp server
     * @param string $host      The host of the ftp server
     * @param array  $options   The options like port, username, password, passive, create, mode
     */
    public function __construct($directory, $host, $options = array())
    {
        $this->directory = (string) $directory;
        $this->host      = $host;
        $this->port      = isset($options['port']) ? $options['port'] : 21;
        $this->username  = isset($options['username']) ? $options['username'] : null;
        $this->password  = isset($options['password']) ? $options['password'] : null;
        $this->passive   = isset($options['passive']) ? $options['passive'] : false;
        $this->create    = isset($options['create']) ? $options['create'] : false;
        $this->mode      = isset($options['mode']) ? $options['mode'] : FTP_BINARY;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $temp = fopen('php://temp', 'r+');

        if (!ftp_fget($this->getConnection(), $temp, $this->computePath($key), $this->mode)) {
            return false;
        }

        rewind($temp);
        $contents = stream_get_contents($temp);
        fclose($temp);

        return $contents;
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content)
    {
        $path = $this->computePath($key);
        $directory = dirname($path);

        $this->ensureDirectoryExists($directory, true);

        $temp = fopen('php://temp', 'r+');
        $size = fwrite($temp, $content);
        rewind($temp);

        if (!ftp_fput($this->getConnection(), $path, $temp, $this->mode)) {
            fclose($temp);

            return false;
        }

        fclose($temp);

        return $size;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $sourcePath = $this->computePath($sourceKey);
        $targetPath = $this->computePath($targetKey);

        $this->ensureDirectoryExists(dirname($targetPath), true);

        return ftp_rename($this->getConnection(), $sourcePath, $targetPath);
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        $file  = $this->computePath($key);
        $items = ftp_nlist($this->getConnection(), dirname($file));

        return $items && (in_array($file, $items) || in_array(basename($file), $items));
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        return $this->fetchKeys();
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        $mtime = ftp_mdtm($this->getConnection(), $this->computePath($key));

        // the server does not support this function
        if (-1 === $mtime) {
            throw new \RuntimeException('Server does not support ftp_mdtm function.');
        }

        return $mtime;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        if ($this->isDirectory($key)) {
            return ftp_rmdir($this->getConnection(), $this->computePath($key));
        }

        return ftp_delete($this->getConnection(), $this->computePath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function isDirectory($key)
    {
        return $this->isDir($this->computePath($key));
    }

    /**
     * Lists files from the specified directory. If a pattern is
     * specified, it only returns files matching it.
     *
     * @param string $directory The path of the directory to list from
     *
     * @return array An array of keys and dirs
     */
    public function listDirectory($directory = '')
    {
        $directory = preg_replace('/^[\/]*([^\/].*)$/', '/$1', $directory);

        $items = $this->parseRawlist(
            ftp_rawlist($this->getConnection(), $this->directory . $directory ) ? : array()
        );

        $fileData = $dirs = array();
        foreach ($items as $itemData) {
            
            if ('..' === $itemData['name'] || '.' === $itemData['name']) {
                continue;
            }
            
            $item = array(
                'name'  => $itemData['name'],
                'path'  => trim(($directory ? $directory . '/' : '') . $itemData['name'], '/'),
                'time'  => $itemData['time'],
                'size'  => $itemData['size'],
            );

            if ('-' === substr($itemData['perms'], 0, 1)) {
                $fileData[$item['path']] = $item;
            } elseif ('d' === substr($itemData['perms'], 0, 1)) {
                $dirs[] = $item['path'];
            }
        }

        $this->fileData = array_merge($fileData, $this->fileData);

        return array(
           'keys'   => array_keys($fileData),
           'dirs'   => $dirs
        );
    }

    /**
     * {@inheritDoc}
     */
    public function createFile($key, Filesystem $filesystem)
    {
        $file = new File($key, $filesystem);

        if (!array_key_exists($key, $this->fileData)) {
            $directory = dirname($key) == '.' ? '' : dirname($key);
            $this->listDirectory($directory);
        }

        $fileData = $this->fileData[$key];

        $file->setName($fileData['name']);
        $file->setSize($fileData['size']);

        return $file;
    }

    /**
     * Ensures the specified directory exists. If it does not, and the create
     * parameter is set to TRUE, it tries to create it
     *
     * @param string  $directory
     * @param boolean $create    Whether to create the directory if it does not
     *                         exist
     *
     * @throws RuntimeException if the directory does not exist and could not
     *                          be created
     */
    protected function ensureDirectoryExists($directory, $create = false)
    {
        if (!$this->isDir($directory)) {
            if (!$create) {
                throw new \RuntimeException(sprintf('The directory \'%s\' does not exist.', $directory));
            }

            $this->createDirectory($directory);
        }
    }

    /**
     * Creates the specified directory and its parent directories
     *
     * @param string $directory Directory to create
     *
     * @throws RuntimeException if the directory could not be created
     */
    protected function createDirectory($directory)
    {
        // create parent directory if needed
        $parent = dirname($directory);
        if (!$this->isDir($parent)) {
            $this->createDirectory($parent);
        }

        // create the specified directory
        $created = ftp_mkdir($this->getConnection(), $directory);
        if (false === $created) {
            throw new \RuntimeException(sprintf('Could not create the \'%s\' directory.', $directory));
        }
    }

    /**
     * @param  string  $directory - full directory path
     * @return boolean
     */
    private function isDir($directory)
    {
        if ('/' === $directory) {
            return true;
        }

        if (!@ftp_chdir($this->getConnection(), $directory)) {
            return false;
        }

        // change directory again to return in the base directory
        ftp_chdir($this->getConnection(), $this->directory);

        return true;
    }

    /**
     * Fetch all Keys recursive
     *
     * @param string $directory
     */
    private function fetchKeys($directory = '')
    {
        $items = $this->listDirectory($directory);

        $keys = $items['dirs'];
        foreach ($items['dirs'] as $dir) {
            $keys = array_merge($keys, $this->fetchKeys($dir));
        }

        return array_merge($items['keys'], $keys);
    }

    /**
     * Parses the given raw list
     *
     * @param array $rawlist
     *
     * @return array
     */
    private function parseRawlist(array $rawlist)
    {
        $parsed = array();
        foreach ($rawlist as $line) {
            $infos = preg_split("/[\s]+/", $line, 9);
            $infos[7] = (strrpos($infos[7], ':') != 2 ) ? ($infos[7] . ' 00:00') : (date('Y') . ' ' . $infos[7]);

            if ('total' !== $infos[0]) {
                $parsed[] = array(
                    'perms' => $infos[0],
                    'num'   => $infos[1],
                    'size'  => $infos[4],
                    'time'  => strtotime($infos[5] . ' ' . $infos[6] . '. ' . $infos[7]),
                    'name'  => $infos[8]
                );
            }
        }

        return $parsed;
    }

    /**
     * Computes the path for the given key
     *
     * @param string $key
     */
    private function computePath($key)
    {
        return rtrim($this->directory, '/') . '/' . $key;
    }

    /**
     * Indicates whether the adapter has an open ftp connection
     *
     * @return boolean
     */
    private function isConnected()
    {
        return is_resource($this->connection);
    }

    /**
     * Returns an opened ftp connection resource. If the connection is not
     * already opened, it open it before
     *
     * @return resource The ftp connection
     */
    private function getConnection()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * Opens the adapter's ftp connection
     *
     * @throws RuntimeException if could not connect
     */
    private function connect()
    {
        // open ftp connection
        $this->connection = ftp_connect($this->host, $this->port);
        if (!$this->connection) {
            throw new \RuntimeException(sprintf('Could not connect to \'%s\' (port: %s).', $this->host, $this->port));
        }

        $username = $this->username ? : 'anonymous';
        $password = $this->password ? : '';

        // login ftp user
        if (!ftp_login($this->connection, $username, $password)) {
            $this->close();
            throw new \RuntimeException(sprintf('Could not login as %s.', $this->username));
        }

        // switch to passive mode if needed
        if ($this->passive && !ftp_pasv($this->connection, true)) {
            $this->close();
            throw new \RuntimeException('Could not turn passive mode on.');
        }

        // ensure the adapter's directory exists
        if ('/' !== $this->directory) {
            try {
                $this->ensureDirectoryExists($this->directory, $this->create);
            } catch (\RuntimeException $e) {
                $this->close();
                throw $e;
            }

            // change the current directory for the adapter's directory
            if (!ftp_chdir($this->connection, $this->directory)) {
                $this->close();
                throw new \RuntimeException(sprintf('Could not change current directory for the \'%s\' directory.', $this->directory));
            }
        }
    }

    /**
     * Closes the adapter's ftp connection
     */
    private function close()
    {
        if ($this->isConnected()) {
            ftp_close($this->connection);
        }
    }

    /**
     * Asserts the specified file exists
     *
     * @param string $key
     *
     * @throws Exception\FileNotFound if the file does not exist
     */
    private function assertExists($key)
    {
        if (!$this->exists($key)) {
            throw new Exception\FileNotFound($key);
        }
    }
}
