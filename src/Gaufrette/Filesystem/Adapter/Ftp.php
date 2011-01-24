<?php

namespace Gaufrette\Filesystem\Adapter;

use Gaufrette\Filesystem\Adapter;

/**
 * Ftp adapter
 *
 * This adapter is not cached, if you need it to be cached, please see the
 * CachedFtp adapter which is a proxy class implementing a cache layer.
 *
 * @packageGaufrette
 * @author  Antoine Hérault <antoine.herault@gmail.com>
 */
class Ftp implements Adapter
{
    protected $connection = null;
    protected $directory;
    protected $host;
    protected $port;
    protected $username;
    protected $password;
    protected $passive;

    /**
     * Constructor
     *
     * @param  string $directory
     * @param  string $host
     * @param  string $port
     * @param  string $username
     * @param  string $password
     * @param  string $passive (default FALSE)
     */
    public function __construct($directory, $host, $port, $username, $password, $passive = false)
    {
        $this->directory = $directory;
        $this->host = $host;
        $this->port = $port
        $this->username = $username;
        $this->password = $password;
        $this->passive = $passive;
    }

    /**
     * {@InheritDoc}
     */
    public function read($key)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $temp = fopen('php://temp', 'r+');
        if (!ftp_fget($this->connection, $temp, $this->computePath($key), FTP_ASCII)) {
            throw new \RuntimeException(sprintf('Could not read file \'%s\'.', $key));
        }

        rewind($temp);

        return stream_get_contents($temp);
    }

    /**
     * {@InheritDoc}
     */
    public function write($key, $content)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->ensureDirectoryExists($this->computePath($key));

        $temp = fopen('php://temp', 'r+');
        $size = fwrite($temp, $content);
        if (!ftp_fput($this->connection, $this->computePath($key), $temp, FTP_ASCII)) {
            throw new \RuntimeException(sprintf('Could not write file \'%s\'.', $key));
        }

        return $size;
    }

    /**
     * {@InheritDoc}
     */
    public function exists($key)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $files = ftp_nlist($this->connection, dirname($this->computePath($key)));
        foreach ($files as $file) {
            if ($key === $file) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@InheritDoc}
     */
    public function keys($pattern)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        throw new \Exception('Shame on me, I should have implemented this method.');
    }

    /**
     * {@InheritDoc}
     */
    public function mtime($key)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $mtime = ftp_mdtm($this->connection, $this->computePath($key));

        // the server does not support this function
        if (-1 === $mtime) {
            throw new \RuntimeException(sprintf('Could not get the last modified time of the \'%s\' file.', $key));
        }

        return $mtime;
    }

    /**
     * {@InheritDoc}
     */
    public function delete($key)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        return ftp_delete($this->connection, $this->computePath($key));
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
        if ($this->directoryExists($directory)) {
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
        if (!$this->isConnected()) {
            $this->connect();
        }

        if (!ftp_chdir($this->connection, $directory)) {
            return false;
        }

        // change directory again to return in the base directory
        ftp_chdir($this->directory);

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
        if (!$this->isConnected()) {
            $this->connect();
        }

        // create parent directory if needed
        $parent = dirname($directory);
        if (!$this->directoryExists($parent)) {
            $this->createDirectory($parent);
        }

        // create the specified directory
        $created = ftp_mkdir($this->connection, $directory);
        if (false === $created) {
            throw new \RuntimeException(sprintf('Could not create the \'%s\' directory.', $directory));
        }
    }

    /**
     * Computes the path for the given key
     *
     * @param  string $key
     *
     * @todo Rename this method (is it really mandatory)
     */
    public function computePath($key)
    {
        return $key;
    }

    /**
     * Indicates whether the adapter has an open ftp connection
     *
     * @return boolean
     */
    public function isConnected()
    {
        return is_resource($this->connection);
    }

    /**
     * Opens the adapter's ftp connection
     *
     * @throws RuntimeException if could not connect
     */
    public function connect()
    {
        // open ftp connection
        $this->connection = ftp_connect($this->host, $this->port);
        if (!$connection) {
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

    /**
     * Closes the adapter's ftp connection
     */
    public function close()
    {
        if ($this->isConnected()) {
            ftp_close($this->connection);
        }
    }
}
