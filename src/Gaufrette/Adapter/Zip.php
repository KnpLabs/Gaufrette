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
final class Zip implements Adapter
{
    protected ZipArchive $zipArchive;

    public function __construct(private string $zipFile)
    {
        if (!extension_loaded()) {
            throw new \RuntimeException(sprintf('Unable to use %s as the ZIP extension is not available.', self::class));
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
        return (bool) $this->getStat($key);
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
        // @phpstan-ignore-next-line isset.initializedProperty (guards the case where the constructor throws before reinitZipArchive() runs)
        if (isset($this->zipArchive)) {
            try {
                $this->zipArchive->close();
            } catch (\Exception) {
            }
            unset($this->zipArchive);
        }
    }

    protected function reinitZipArchive(): self
    {
        $this->zipArchive = new ZipArchive();

        if (true !== ($resultCode = $this->zipArchive->open($this->zipFile, ZipArchive::CREATE))) {
            $errMsg = match ($resultCode) {
                ZipArchive::ER_EXISTS => 'File already exists.',
                ZipArchive::ER_INCONS => 'Zip archive inconsistent.',
                ZipArchive::ER_INVAL => 'Invalid argument.',
                ZipArchive::ER_MEMORY => 'Malloc failure.',
                ZipArchive::ER_NOENT => 'Invalid argument.',
                ZipArchive::ER_NOZIP => 'Not a zip archive.',
                ZipArchive::ER_OPEN => 'Can\'t open file.',
                ZipArchive::ER_READ => 'Read error.',
                ZipArchive::ER_SEEK => 'Seek error.',
                default => 'Unknown error.',
            };

            throw new \RuntimeException($errMsg);
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
