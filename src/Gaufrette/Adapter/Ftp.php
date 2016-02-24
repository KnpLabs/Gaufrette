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
 * @author <f.larmagna@gmail.com>
 */
class Ftp implements Adapter,
                     FileFactory,
                     ListKeysAware
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
    protected $ssl;
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
        if (!extension_loaded('ftp')) {
            throw new \RuntimeException('Unable to use Gaufrette\Adapter\Ftp as the FTP extension is not available.');
        }

        $this->directory = (string) $directory;
        $this->host      = $host;
        $this->port      = isset($options['port']) ? $options['port'] : 21;
        $this->username  = isset($options['username']) ? $options['username'] : null;
        $this->password  = isset($options['password']) ? $options['password'] : null;
        $this->passive   = isset($options['passive']) ? $options['passive'] : false;
        $this->create    = isset($options['create']) ? $options['create'] : false;
        $this->mode      = isset($options['mode']) ? $options['mode'] : FTP_BINARY;
        $this->ssl       = isset($options['ssl']) ? $options['ssl'] : false;
    }

    /**
     * @param string $key
     * @return bool|string
     */
    public function read($key)
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

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
     * @param string $key
     * @param string $content
     * @return bool|int
     */
    public function write($key, $content)
    {
        //change to the directory that will contain the written file
        $this->moveToTargetDirectory($key);
        $file = basename($key);

        $temp = fopen('php://temp', 'r+');
        $size = fwrite($temp, $content);
        rewind($temp);

        if (!ftp_fput($this->getConnection(), $file, $temp, $this->mode)) {
            //change back to ftp root directory
            $this->changeDirectory($this->directory);
            fclose($temp);

            return false;
        }

        //change back to ftp root directory
        $this->changeDirectory($this->directory);
        fclose($temp);

        return $size;
    }

    /**
     * @param string $sourceKey
     * @param string $targetKey
     * @return bool
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $sourcePath = $this->computePath($sourceKey);
        $targetPath = $this->computePath($targetKey);

        $this->ensureDirectoryExists(dirname($targetPath), true);

        return ftp_rename($this->getConnection(), $sourcePath, $targetPath);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function exists($key)
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $file  = $this->computePath($key);
        $lines = ftp_rawlist($this->getConnection(), '-al ' . dirname($file));

        if (false === $lines) {
            return false;
        }

        $pattern = '{(?<!->) '.preg_quote(basename($file)).'( -> |$)}m';
        foreach ($lines as $line) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function keys()
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $keys = $this->fetchKeys();

        return $keys['keys'];
    }

    /**
     * @param string $prefix
     * @return array
     */
    public function listKeys($prefix = '')
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        preg_match('/(.*?)[^\/]*$/', $prefix, $match);
        $directory = rtrim($match[1], '/');

        $keys = $this->fetchKeys($directory, false);

        if ($directory === $prefix) {
            return $keys;
        }

        $filteredKeys = array();
        foreach (array('keys', 'dirs') as $hash) {
            $filteredKeys[$hash] = array();
            foreach ($keys[$hash] as $key) {
                if (0 === strpos($key, $prefix)) {
                    $filteredKeys[$hash][] = $key;
                }
            }
        }

        return $filteredKeys;
    }

    /**
     * @param string $key
     * @return int
     * @throws \RuntimeException
     */
    public function mtime($key)
    {
        //change directory to avoid truncation of too long paths
        $this->moveToTargetDirectory($key);
        $file = basename($key);

        $mtime = ftp_mdtm($this->getConnection(), $file);
        //change back to ftp root directory
        $this->changeDirectory($this->directory);

        // the server does not support this function
        if (-1 === $mtime) {
            throw new \RuntimeException('Server does not support ftp_mdtm function.');
        }

        return $mtime;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key)
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        if ($this->isDirectory($key)) {
            return ftp_rmdir($this->getConnection(), $this->computePath($key));
        }

        return ftp_delete($this->getConnection(), $this->computePath($key));
    }

    /**
     * @param string $key
     * @return bool
     */
    public function isDirectory($key)
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

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
        $this->ensureDirectoryExists($this->directory, $this->create);

        $directory = preg_replace('/^[\/]*([^\/].*)$/', '/$1', $directory);

        $items = $this->parseRawlist(
            ftp_rawlist($this->getConnection(), '-al ' . $this->directory . $directory ) ? : array()
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
     * @param string $key
     * @param Filesystem $filesystem
     * @return File
     */
    public function createFile($key, Filesystem $filesystem)
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $file = new File($key, $filesystem);

        if (!array_key_exists($key, $this->fileData)) {
            $directory = dirname($key) == '.' ? '' : dirname($key);
            $this->listDirectory($directory);
        }

        if (isset($this->fileData[$key])) {
            $fileData = $this->fileData[$key];

            $file->setName($fileData['name']);
            $file->setSize($fileData['size']);
        }

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
     * @param string $directory
     * @param bool|true $onlyKeys
     * @return array
     */
    private function fetchKeys($directory = '', $onlyKeys = true)
    {
        $directory = preg_replace('/^[\/]*([^\/].*)$/', '/$1', $directory);

        $lines = ftp_rawlist($this->getConnection(), '-alR '. $this->directory . $directory);

        if (false === $lines) {
            return array('keys' => array(), 'dirs' => array());
        }

        $regexDir = '/'.preg_quote($this->directory . $directory, '/').'\/?(.+):$/u';
        $regexItem = '/^(?:([d\-\d])\S+)\s+\S+(?:(?:\s+\S+){5})?\s+(\S+)\s+(.+?)$/';

        $prevLine = null;
        $directories = array();
        $keys = array('keys' => array(), 'dirs' => array());

        foreach ((array) $lines as $line) {
            if ('' === $prevLine && preg_match($regexDir, $line, $match)) {
                $directory = $match[1];
                unset($directories[$directory]);
                if ($onlyKeys) {
                    $keys = array(
                        'keys' => array_merge($keys['keys'], $keys['dirs']),
                        'dirs' => array()
                    );
                }
            } elseif (preg_match($regexItem, $line, $tokens)) {
                $name = $tokens[3];

                if ('.' === $name || '..' === $name) {
                    continue;
                }

                $path = ltrim($directory . '/' . $name, '/');

                if ('d' === $tokens[1] || '<dir>' === $tokens[2]) {
                    $keys['dirs'][] = $path;
                    $directories[$path] = true;
                } else {
                    $keys['keys'][] = $path;
                }
            }
            $prevLine = $line;
        }

        if ($onlyKeys) {
            $keys = array(
                'keys' => array_merge($keys['keys'], $keys['dirs']),
                'dirs' => array()
            );
        }

        foreach (array_keys($directories) as $directory) {
            $keys = array_merge_recursive($keys, $this->fetchKeys($directory, $onlyKeys));
        }

        return $keys;
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

            if ($this->isLinuxListing($infos)) {
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
            } else {
                $isDir = (boolean) ('<dir>' === $infos[2]);
                $parsed[] = array(
                    'perms' => $isDir ? 'd' : '-',
                    'num'   => '',
                    'size'  => $isDir ? '' : $infos[2],
                    'time'  => strtotime($infos[0] . ' ' . $infos[1]),
                    'name'  => $infos[3]
                );
            }
        }

        return $parsed;
    }

    /**
     * Computes the path for the given key
     *
     * @param $key
     * @return string
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
        if (!$this->ssl) {
            $this->connection = ftp_connect($this->host, $this->port);
        } else {
            if(function_exists('ftp_ssl_connect')) {
                $this->connection = ftp_ssl_connect($this->host, $this->port);
            } else {
                throw new \RuntimeException('This Server Has No SSL-FTP Available.');
            }
        }
        if (!$this->connection) {
            throw new \RuntimeException(sprintf('Could not connect to \'%s\' (port: %s).', $this->host, $this->port));
        }

        $username = $this->username ? : 'anonymous';
        $password = $this->password ? : '';

        // login ftp user
        if (!@ftp_login($this->connection, $username, $password)) {
            $this->close();
            throw new \RuntimeException(sprintf('Could not login as %s.', $username));
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
     * @param $info
     * @return bool
     */
    private function isLinuxListing($info)
    {
        return count($info) >= 9;
    }

    /**
     * Change current directory to the specified directory
     *
     * @param string $directory
     * @throws \RuntimeException
     * @return bool
     */
    protected function changeDirectory($directory)
    {
        $moved = ftp_chdir($this->getConnection(), $directory);

        if (false === $moved) {
            throw new \RuntimeException(sprintf('Could not move to the \'%s\' directory.', $directory));
        }
    }

    /**
     * Change current directory to the directory of
     * the $key filepath.
     * This allows to avoid truncation of too long file paths
     * or failure of ftp_mdtm function due to too long file paths
     *
     * @param string $key
     */
    protected function moveToTargetDirectory($key)
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        $path = $this->computePath($key);
        $directory = dirname($path);

        $this->ensureDirectoryExists($directory, true);

        $this->changeDirectory($directory);
    }
}
