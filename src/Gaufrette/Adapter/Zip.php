<?php

namespace Gaufrette\Adapter;

use ZipArchive;
use Gaufrette\Adapter;
use Gaufrette\Util;

/**
 * ZIP Archive adapter.
 *
 * @author Boris Guéry <guery.b@gmail.com>
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class Zip implements Adapter
{
    protected ZipArchive $zipArchive;

    public function __construct(private readonly string $zipFile)
    {
        if (!extension_loaded('zip')) {
            throw new \RuntimeException(sprintf('Unable to use %s as the ZIP extension is not available.', __CLASS__));
        }

        $this->reinitZipArchive();
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|bool
    {
        if (false === ($content = $this->zipArchive->getFromName($key, 0))) {
            return false;
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, mixed $content): int|bool
    {
        if (!$this->zipArchive->addFromString($key, $content)) {
            return false;
        }

        if (!$this->save()) {
            return false;
        }

        return Util\Size::fromContent($content);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return (boolean) $this->getStat($key);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        $keys = [];

        for ($i = 0; $i < $this->zipArchive->numFiles; ++$i) {
            $keys[$i] = $this->zipArchive->getNameIndex($i);
        }

        return $keys;
    }

    /**
     * @todo implement
     *
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|bool
    {
        $stat = $this->getStat($key);

        return $stat['mtime'] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        if (!$this->zipArchive->deleteName($key)) {
            return false;
        }

        return $this->save();
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        if (!$this->zipArchive->renameName($sourceKey, $targetKey)) {
            return false;
        }

        return $this->save();
    }

    /**
     * Returns the stat of a file in the zip archive
     *  (name, index, crc, mtime, compression size, compression method, filesize).
     */
    public function getStat(string $key): array
    {
        $stat = $this->zipArchive->statName($key);
        if (false === $stat) {
            return [];
        }

        return $stat;
    }

    public function __destruct()
    {
        if ($this->zipArchive) {
            try {
                $this->zipArchive->close();
            } catch (\Exception $e) {
            }
            unset($this->zipArchive);
        }
    }

    protected function reinitZipArchive(): self
    {
        $this->zipArchive = new ZipArchive();

        if (true !== ($resultCode = $this->zipArchive->open($this->zipFile, ZipArchive::CREATE))) {
            switch ($resultCode) {
                case ZipArchive::ER_EXISTS:
                    $errMsg = 'File already exists.';

                    break;
                case ZipArchive::ER_INCONS:
                    $errMsg = 'Zip archive inconsistent.';

                    break;
                case ZipArchive::ER_INVAL:
                    $errMsg = 'Invalid argument.';

                    break;
                case ZipArchive::ER_MEMORY:
                    $errMsg = 'Malloc failure.';

                    break;
                case ZipArchive::ER_NOENT:
                    $errMsg = 'Invalid argument.';

                    break;
                case ZipArchive::ER_NOZIP:
                    $errMsg = 'Not a zip archive.';

                    break;
                case ZipArchive::ER_OPEN:
                    $errMsg = 'Can\'t open file.';

                    break;
                case ZipArchive::ER_READ:
                    $errMsg = 'Read error.';

                    break;
                case ZipArchive::ER_SEEK:
                    $errMsg = 'Seek error.';

                    break;
                default:
                    $errMsg = 'Unknown error.';

                    break;
            }

            throw new \RuntimeException(sprintf('%s', $errMsg));
        }

        return $this;
    }

    /**
     * Saves archive modifications and updates current ZipArchive instance.
     *
     * @throws \RuntimeException If file could not be saved
     */
    protected function save(): bool
    {
        // Close to save modification
        if (!$this->zipArchive->close()) {
            return false;
        }

        // Re-initialize to get updated version
        $this->reinitZipArchive();

        return true;
    }
}
