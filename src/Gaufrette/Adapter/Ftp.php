<?php

namespace Gaufrette\Adapter;

use Gaufrette\File;
use Gaufrette\Filesystem;

/**
 * Ftp adapter
 *
 * This adapter is not cached, if you need it to be cached, please see the
 * CachedFtp adapter which is a proxy class implementing a cache layer.
 *
 * @package Gaufrette
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class Ftp extends Base
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
     * @param  string $directory The directory to use in the ftp server
     * @param  string $host      The host of the ftp server
     * @param  string $username  The username
     * @param  string $password  The password
     * @param  string $port      The ftp port (default 21)
     * @param  string $passive   Whether to switch the ftp connection in passive
     *                           mode (default FALSE)
     * @param  string $create    Whether to create the directory if it does not
     *                           exist
     * @param  string $mode      Transfer-Mode (FTP_ASCII oder FTP_BINARY)
     */
    public function __construct($directory, $host, $username = null, $password = null, $port = 21, $passive = false, $create = false, $mode = FTP_ASCII)
    {
        $this->directory = $directory;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->passive = $passive;
        $this->create = $create;
        $this->mode = $mode;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $temp = fopen('php://temp', 'r+');

        if (!ftp_fget($this->getConnection(), $temp, $this->computePath($key), $this->mode)) {
            throw new \RuntimeException(sprintf('Could not read the \'%s\' file.', $key));
        }

        rewind($temp);
        $contents = stream_get_contents($temp);
        fclose($temp);

        return $contents;
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $path = $this->computePath($key);
        $directory = dirname($path);

        $this->ensureDirectoryExists($directory, true);

        $temp = fopen('php://temp', 'r+');
        $size = fwrite($temp, $content);
        rewind($temp);

        if (!ftp_fput($this->getConnection(), $path, $temp, $this->mode)) {
            throw new \RuntimeException(sprintf('Could not write the \'%s\' file.', $key));
        }

        fclose($temp);

        return $size;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($key, $new)
    {
        $old = $this->computePath($key);
        $path = $this->computePath($new);
        $directory = dirname($new);

        $this->ensureDirectoryExists($directory, true);

        if(!ftp_rename($this->getConnection(), $old, $path)) {
            throw new \RuntimeException(sprintf('Could not rename the \'%s\' file to \'%s\'.', $key, $new));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        if (array_key_exists($key, $this->fileData)) {
            return true;
        } else {
            $file = $this->computePath($key);

            $items = ftp_nlist($this->getConnection(), dirname($file));
            foreach ($items as $item) {
                if (basename($file) === $item) {
                    return true;
                }
            }
        }

        return false;
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
            throw new \RuntimeException(sprintf('Could not get the last modified time of the \'%s\' file.', $key));
        }

        return $mtime;
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        return md5($this->read($key));
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        if (!ftp_delete($this->getConnection(), $this->computePath($key))) {
            throw new \RuntimeException(sprintf('Could not delete the \'%s\' file.', $key));
        }
    }

    /**
     * Ensures the specified directory exists. If it does not, and the create
     * parameter is set to TRUE, it tries to create it
     *
     * @param  string  $directory
     * @param  boolean $create Whether to create the directory if it does not
     *                         exist
     *
     * @throws RuntimeException if the directory does not exist and could not
     *                          be created
     */
    public function ensureDirectoryExists($directory, $create = false)
    {
        if (!$this->directoryExists($directory)) {
            if (!$create) {
                throw new \RuntimeException(sprintf('The directory \'%s\' does not exist.', $directory));
            }

            $this->createDirectory($directory);
        }
    }

    /**
     * Indicates whether the specified directory exists
     *
     * @param  string $directory
     *
     * @return boolean TRUE if the directory exists, FALSE otherwise
     */
    public function directoryExists($directory)
    {
        if (!ftp_chdir($this->getConnection(), $directory)) {
            return false;
        }

        // change directory again to return in the base directory
        ftp_chdir($this->getConnection(), $this->directory);

        return true;
    }

    /**
     * Creates the specified directory and its parent directories
     *
     * @param  string $directory Directory to create
     *
     * @throws RuntimeException if the directory could not be created
     */
    public function createDirectory($directory)
    {
        // create parent directory if needed
        $parent = dirname($directory);
        if (!$this->directoryExists($parent)) {
            $this->createDirectory($parent);
        }

        // create the specified directory
        $created = ftp_mkdir($this->getConnection(), $directory);
        if (false === $created) {
            throw new \RuntimeException(sprintf('Could not create the \'%s\' directory.', $directory));
        }
    }

    /**
     * Lists files from the specified directory. If a pattern is
     * specified, it only returns files matching it.
     *
     * @param  string $directory The path of the directory to list from
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
            $item = array(
                'name'  => $itemData['name'],
                'path'  => trim(($directory ? $directory . '/' : '') . $itemData['name'], '/'),
                'time'  => $itemData['time'],
                'size'  => $itemData['size'],
            );

            if ('-' === substr($itemData['perms'], 0, 1)) {
                $fileData[$item['path']] = $item;
            } elseif('d' === substr($itemData['perms'], 0, 1)) {
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
    public function supportsMetadata()
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function createFile($key, Filesystem $filesystem)
    {
        if (!$this->exists($key)) {
            throw new \RuntimeException(sprintf('The \'%s\' file does not exist.', $key));
        }

        $file = new File($key, $filesystem);

        if (!array_key_exists($key, $this->fileData)) {
            $directory = dirname($key) == '.' ? '' : dirname($key);
            $this->listDirectory($directory);
        }

        $fileData = $this->fileData[$key];

        $created = new \DateTime();
        $created->setTimestamp($fileData['time']);

        $file->setName($fileData['name']);
        $file->setCreated($created);
        $file->setSize($fileData['size']);

        return $file;
    }

    /**
     * Fetch all Keys recursive
     *
     * @param string $directory
     */
    private function fetchKeys($directory = '')
    {
        $items = $this->listDirectory($directory);

        $keys = array();
        foreach ($items['dirs'] as $dir) {
            $keys = $this->fetchKeys($dir);
        }

        return array_merge($items['keys'], $keys);
    }

    /**
     * Parses the given raw list
     *
     * @param  array $rawlist
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
     * @param  string $key
     */
    private function computePath($key)
    {
        return $this->directory . '/' . $key;
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
        if (!empty($this->directory)) {
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
}
