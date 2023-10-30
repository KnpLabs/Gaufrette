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
    protected Adapter $adapter;

    /**
     * Contains File objects created with $this->createFile() method.
     *
     * @var array
     */
    protected array $fileRegister = [];

    /**
     * @param Adapter $adapter A configured Adapter instance
     */
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        self::assertValidKey($key);

        return $this->adapter->exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
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
    public function get(string $key, bool $create = false): File
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
    public function write(string $key, string $content, bool $overwrite = false): int
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
    public function read(string $key): string
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
    public function delete(string $key): bool
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
    public function keys(): array
    {
        return $this->adapter->keys();
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(string $prefix = ''): array
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
    public function mtime(string $key): int|bool
    {
        self::assertValidKey($key);

        $this->assertHasFile($key);

        return $this->adapter->mtime($key);
    }

    /**
     * {@inheritdoc}
     */
    public function checksum(string $key): string
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
    public function size(string $key): int
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
    public function createStream(string $key): Stream|Stream\InMemoryBuffer
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
    public function createFile(string $key): File
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
    public function mimeType(string $key): string|bool
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
     * @throws Exception\FileNotFound when sourceKey does not exist
     */
    private function assertHasFile(string $key): void
    {
        if (!$this->has($key)) {
            throw new Exception\FileNotFound($key);
        }
    }

    /**
     * Checks if matching File object by given key exists in the fileRegister.
     */
    private function isFileInRegister(string $key): bool
    {
        return array_key_exists($key, $this->fileRegister);
    }

    /**
     * Clear files register.
     */
    public function clearFileRegister(): void
    {
        $this->fileRegister = [];
    }

    /**
     * Removes File object from register.
     */
    public function removeFromRegister(string $key): void
    {
        if ($this->isFileInRegister($key)) {
            unset($this->fileRegister[$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        return $this->adapter->isDirectory($key);
    }

    /**
     * @throws \InvalidArgumentException Given $key should not be empty
     */
    private static function assertValidKey(string $key): void
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Object path is empty.');
        }
    }
}
