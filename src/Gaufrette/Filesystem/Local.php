<?php
namespace Gaufrette\Filesystem;

use Gaufrette\File as AbstractFile;
use Gaufrette\File\Local as File;
use Gaufrette\Filesystem;
use Gaufrette\Util;
use Gaufrette\Stream;
use Gaufrette\StreamFactory;
use Gaufrette\Exception;

/**
 * Adapter for the local filesystem
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 * @author Tomi Saarinen <tomi.saarinen@rohea.com>
 */
class Local implements Filesystem,
                       StreamFactory
{
    protected $directory;
    private $create;

    /**
     * Constructor
     *
     * @param string  $directory Directory where the filesystem is located
     * @param boolean $create    Whether to create the directory if it does not
     *                            exist (default FALSE)
     *
     * @throws RuntimeException if the specified directory does not exist and
     *                          could not be created
     */
    public function __construct($directory, $create = false)
    {
        $this->directory = Util\Path::normalize($directory);

        if (is_link($this->directory)) {
            $this->directory = realpath($this->directory);
        }

        $this->create = $create;
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        return file_exists($this->computePath($key));
    }    
    
    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $file = new File($key);
        $path = $this->computePath($key);
        $file->setContent(file_get_contents($path));
        //Set data for file (do not set content, it's lazy)
        $file->setName("TODO: Set human-readable filename somehow...");
        $file->setDate(filemtime($path));
        //$file->setChecksum();
        return $file;
    }

    /**
     * {@inheritDoc}
     */
    public function write(AbstractFile $file)
    {
        $key = $file->getKey();
        if (! isset($key) || strlen($key."") < 1) {
            throw new \InvalidArgumentException(sprintf('Key is not set for file. Cannot write file.'));
        }
        if (strlen($file->getContent()) < 1) {
            throw new \InvalidArgumentException(sprintf('Content is not for file "%s". Cannot write file.'), $key);            
        }        
        $path = $this->computePath($key);
        $this->ensureDirectoryExists(dirname($path), true);
        file_put_contents($path, $file->getContent());

        return $file;
    }    
    
    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        $targetPath = $this->computePath($targetKey);
        $this->ensureDirectoryExists(dirname($targetPath), true);

        return rename($this->computePath($sourceKey), $targetPath);
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->directory,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
                )
            );
        } catch (\Exception $e) {
            $iterator = new \EmptyIterator;
        }
        $files = iterator_to_array($iterator);

        $keys = array();
        foreach ($files as $file) {
            $keys[] = $key = $this->computeKey($file);
            if ('.' !== dirname($key)) {
                $keys[] = dirname($key);
            }
        }
        sort($keys);

        return $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        if ($this->isDirectory($key)) {
            return rmdir($this->computePath($key));
        }

        return unlink($this->computePath($key));
    }

    /**
     * @param  string  $key
     * @return boolean
     */
    public function isDirectory($key)
    {
        return is_dir($this->computePath($key));
    }

    /**
     * {@inheritDoc}
     */
    public function createStream($key)
    {
        return new Stream\Local($this->computePath($key));
    }

    public function checksum($key)
    {
        return Util\Checksum::fromFile($this->computePath($key));
    }

    /**
     * Computes the key from the specified path
     *
     * @param string $path
     *
     * return string
     */
    public function computeKey($path)
    {
        $path = $this->normalizePath($path);

        return ltrim(substr($path, strlen($this->directory)), '/');
    }

    /**
     * Computes the path from the specified key
     *
     * @param string $key The key which for to compute the path
     *
     * @return string A path
     *
     * @throws OutOfBoundsException If the computed path is out of the
     *                              directory
     * @throws RuntimeException If directory does not exists and cannot be created
     */
    protected function computePath($key)
    {
        $this->ensureDirectoryExists($this->directory, $this->create);

        return $this->normalizePath($this->directory . '/' . $key);
    }

    /**
     * Normalizes the given path
     *
     * @param string $path
     *
     * @return string
     */
    protected function normalizePath($path)
    {
        $path = Util\Path::normalize($path);

        if (0 !== strpos($path, $this->directory)) {
            throw new \OutOfBoundsException(sprintf('The path "%s" is out of the filesystem.', $path));
        }

        return $path;
    }

    /**
     * Ensures the specified directory exists, creates it if it does not
     *
     * @param string  $directory Path of the directory to test
     * @param boolean $create    Whether to create the directory if it does
     *                            not exist
     *
     * @throws RuntimeException if the directory does not exists and could not
     *                          be created
     */
    protected function ensureDirectoryExists($directory, $create = false)
    {
        if (!is_dir($directory)) {
            if (!$create) {
                throw new \RuntimeException(sprintf('The directory "%s" does not exist.', $directory));
            }

            $this->createDirectory($directory);
        }
    }

    /**
     * Creates the specified directory and its parents
     *
     * @param string $directory Path of the directory to create
     *
     * @throws InvalidArgumentException if the directory already exists
     * @throws RuntimeException         if the directory could not be created
     */
    protected function createDirectory($directory)
    {
        $umask = umask(0);
        $created = mkdir($directory, 0777, true);
        umask($umask);

        if (!$created) {
            throw new \RuntimeException(sprintf('The directory \'%s\' could not be created.', $directory));
        }
    }
    
    /**
     * Factory method for a new empty file object
     *
     * @param string key
     *
     * @param File file
     */
    public function createFile($key)
    {
        return new File($key);
    }    
}
