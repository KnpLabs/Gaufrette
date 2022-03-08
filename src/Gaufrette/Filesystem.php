<?php

namespace Gaufrette;

use Gaufrette\Adapter\ListKeysAware;

/**
 * A filesystem is used to store and retrieve files.
 *
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class Filesystem implements FilesystemInterface
{
    protected $adapter;

    /**
     * Contains File objects created with $this->createFile() method.
     *
     * @var array
     */
    protected $fileRegister = [];

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
     * {@inheritdoc}
     */
    public function has($key)
    {
        self::assertValidKey($key);

        return $this->adapter->exists($key);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function keys()
    {
        return $this->adapter->keys();
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys($prefix = '')
    {
        if ($this->adapter instanceof ListKeysAware) {
            return $this->adapter->listKeys($prefix);
        }

        $dirs = [];
        $keys = [];

        foreach ($this->keys() as $key) {
            if (empty($prefix) || 0 === strpos($key, $prefix)) {
                if ($this->adapter->isDirectory($key)) {
                    $dirs[] = $key;
                } else {
                    $keys[] = $key;
                }
            }
        }

        return [
            'keys' => $keys,
            'dirs' => $dirs,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mtime($key)
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        return $this->adapter->mtime($key);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
        $this->fileRegister = [];
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
     * {@inheritdoc}
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
