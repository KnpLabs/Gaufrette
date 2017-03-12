<?php

namespace Gaufrette;

use Gaufrette\Adapter\ListKeysAware;

/**
 * A filesystem is used to store and retrieve files.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class Filesystem
{
    protected $adapter;

    /**
     * Contains File objects created with $this->createFile() method.
     *
     * @var array
     */
    protected $fileRegister = array();

    /**
     * @param Adapter $adapter A configured Adapter instance
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Returns the adapter.
     *
     * @return Adapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Indicates whether the file matching the specified key exists.
     *
     * @param string $key
     *
     * @return bool TRUE if the file exists, FALSE otherwise
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function has($key)
    {
        self::assertValidKey($key);

        return $this->adapter->exists($key);
    }

    /**
     * Renames a file.
     *
     * @param string $sourceKey
     * @param string $targetKey
     *
     * @return bool TRUE if the rename was successful
     *
     * @throws Exception\FileNotFound    when sourceKey does not exist
     * @throws Exception\UnexpectedFile  when targetKey exists
     * @throws \RuntimeException         when cannot rename
     * @throws \InvalidArgumentException If $sourceKey or $targetKey are invalid
     */
    public function rename($sourceKey, $targetKey)
    {
        self::assertValidKey($sourceKey);
        self::assertValidKey($targetKey);

        $this->assertHasFile($sourceKey);

        if ($this->has($targetKey)) {
            throw new Exception\UnexpectedFile($targetKey);
        }

        if (!$this->adapter->rename($sourceKey, $targetKey)) {
            throw new \RuntimeException(sprintf('Could not rename the "%s" key to "%s".', $sourceKey, $targetKey));
        }

        if ($this->isFileInRegister($sourceKey)) {
            $this->fileRegister[$targetKey] = $this->fileRegister[$sourceKey];
            unset($this->fileRegister[$sourceKey]);
        }

        return true;
    }

    /**
     * Returns the file matching the specified key.
     *
     * @param string $key    Key of the file
     * @param bool   $create Whether to create the file if it does not exist
     *
     * @throws Exception\FileNotFound
     * @throws \InvalidArgumentException If $key is invalid
     *
     * @return File
     */
    public function get($key, $create = false)
    {
        self::assertValidKey($key);

        if (!$create) {
            $this->assertHasFile($key);
        }

        return $this->createFile($key);
    }

    /**
     * Writes the given content into the file.
     *
     * @param string $key       Key of the file
     * @param string $content   Content to write in the file
     * @param bool   $overwrite Whether to overwrite the file if exists
     *
     * @throws Exception\FileAlreadyExists When file already exists and overwrite is false
     * @throws \RuntimeException           When for any reason content could not be written
     * @throws \InvalidArgumentException   If $key is invalid
     *
     * @return int The number of bytes that were written into the file
     */
    public function write($key, $content, $overwrite = false)
    {
        self::assertValidKey($key);

        if (!$overwrite && $this->has($key)) {
            throw new Exception\FileAlreadyExists($key);
        }

        $numBytes = $this->adapter->write($key, $content);

        if (false === $numBytes) {
            throw new \RuntimeException(sprintf('Could not write the "%s" key content.', $key));
        }

        return $numBytes;
    }

    /**
     * Reads the content from the file.
     *
     * @param string $key Key of the file
     *
     * @throws Exception\FileNotFound    when file does not exist
     * @throws \RuntimeException         when cannot read file
     * @throws \InvalidArgumentException If $key is invalid
     *
     * @return string
     */
    public function read($key)
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        $content = $this->adapter->read($key);

        if (false === $content) {
            throw new \RuntimeException(sprintf('Could not read the "%s" key content.', $key));
        }

        return $content;
    }

    /**
     * Deletes the file matching the specified key.
     *
     * @param string $key
     *
     * @throws \RuntimeException         when cannot read file
     * @throws \InvalidArgumentException If $key is invalid
     *
     * @return bool
     */
    public function delete($key)
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        if ($this->adapter->delete($key)) {
            $this->removeFromRegister($key);

            return true;
        }

        throw new \RuntimeException(sprintf('Could not remove the "%s" key.', $key));
    }

    /**
     * Returns an array of all keys.
     *
     * @return array
     */
    public function keys()
    {
        return $this->adapter->keys();
    }

    /**
     * Lists keys beginning with given prefix
     * (no wildcard / regex matching).
     *
     * if adapter implements ListKeysAware interface, adapter's implementation will be used,
     * in not, ALL keys will be requested and iterated through.
     *
     * @param string $prefix
     *
     * @return array
     */
    public function listKeys($prefix = '')
    {
        if ($this->adapter instanceof ListKeysAware) {
            return $this->adapter->listKeys($prefix);
        }

        $dirs = array();
        $keys = array();

        foreach ($this->keys() as $key) {
            if (empty($prefix) || 0 === strpos($key, $prefix)) {
                if ($this->adapter->isDirectory($key)) {
                    $dirs[] = $key;
                } else {
                    $keys[] = $key;
                }
            }
        }

        return array(
            'keys' => $keys,
            'dirs' => $dirs,
        );
    }

    /**
     * Returns the last modified time of the specified file.
     *
     * @param string $key
     *
     * @return int An UNIX like timestamp
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function mtime($key)
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        return $this->adapter->mtime($key);
    }

    /**
     * Returns the checksum of the specified file's content.
     *
     * @param string $key
     *
     * @return string A MD5 hash
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function checksum($key)
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        if ($this->adapter instanceof Adapter\ChecksumCalculator) {
            return $this->adapter->checksum($key);
        }

        return Util\Checksum::fromContent($this->read($key));
    }

    /**
     * Returns the size of the specified file's content.
     *
     * @param string $key
     *
     * @return int File size in Bytes
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function size($key)
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        if ($this->adapter instanceof Adapter\SizeCalculator) {
            return $this->adapter->size($key);
        }

        return Util\Size::fromContent($this->read($key));
    }

    /**
     * Gets a new stream instance of the specified file.
     *
     * @param $key
     *
     * @return Stream|Stream\InMemoryBuffer
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function createStream($key)
    {
        self::assertValidKey($key);

        if ($this->adapter instanceof Adapter\StreamFactory) {
            return $this->adapter->createStream($key);
        }

        return new Stream\InMemoryBuffer($this, $key);
    }

    /**
     * Creates a new file in a filesystem.
     *
     * @param $key
     *
     * @return File
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function createFile($key)
    {
        self::assertValidKey($key);

        if (false === $this->isFileInRegister($key)) {
            if ($this->adapter instanceof Adapter\FileFactory) {
                $this->fileRegister[$key] = $this->adapter->createFile($key, $this);
            } else {
                $this->fileRegister[$key] = new File($key, $this);
            }
        }

        return $this->fileRegister[$key];
    }

    /**
     * Get the mime type of the provided key.
     *
     * @param string $key
     *
     * @return string
     *
     * @throws \InvalidArgumentException If $key is invalid
     */
    public function mimeType($key)
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        if ($this->adapter instanceof Adapter\MimeTypeProvider) {
            return $this->adapter->mimeType($key);
        }

        throw new \LogicException(sprintf(
            'Adapter "%s" cannot provide MIME type',
            get_class($this->adapter)
        ));
    }

    /**
     * Checks if matching file by given key exists in the filesystem.
     *
     * Key must be non empty string, otherwise it will throw Exception\FileNotFound
     * {@see http://php.net/manual/en/function.empty.php}
     *
     * @param string $key
     *
     * @throws Exception\FileNotFound when sourceKey does not exist
     */
    private function assertHasFile($key)
    {
        if (!$this->has($key)) {
            throw new Exception\FileNotFound($key);
        }
    }

    /**
     * Checks if matching File object by given key exists in the fileRegister.
     *
     * @param string $key
     *
     * @return bool
     */
    private function isFileInRegister($key)
    {
        return array_key_exists($key, $this->fileRegister);
    }

    /**
     * Clear files register.
     */
    public function clearFileRegister()
    {
        $this->fileRegister = array();
    }

    /**
     * Removes File object from register.
     *
     * @param string $key
     */
    public function removeFromRegister($key)
    {
        if ($this->isFileInRegister($key)) {
            unset($this->fileRegister[$key]);
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isDirectory($key)
    {
        return $this->adapter->isDirectory($key);
    }

    /**
     * @param string $key
     *
     * @throws \InvalidArgumentException Given $key should not be empty
     */
    private static function assertValidKey($key)
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Object path is empty.');
        }
    }
}
