<?php
namespace Gaufrette\Adapter;

use ZipArchive;

/**
 * ZIP Archive adapter
 *
 * @package Gaufrette
 * @author  Boris Guéry <guery.b@gmail.com>
 */
class Zip extends Base {

    /**
     * @var string The zip archive full path
     */
    protected $zipFile;

    /**
     * @var ZipArchive
     */
    protected $zipArchive;

    public function __construct($zipFile)
    {
        if (!extension_loaded('zip')) {
            throw new \RuntimeException(sprintf('Unable to use %s without ZIP extension installed. See http://www.php.net/manual/en/zip.installation.php', __CLASS__));
        }

        $this->zipFile = $zipFile;
        $this->initZipArchive();
    }

    /**
     * Reads the content of the file
     *
     * @param  string $key
     *
     * @return string
     */
    public function read($key)
    {
        if (false === ($content = $this->zipArchive->getFromName($key, 0))) {
            throw new \RuntimeException(sprintf('Could not read the \'%s\' file.', $key));
        }

        return $content;
    }

    /**
     * Writes the given content into the file
     *
     * @param  string $key
     * @param  string $content
     * @param  array $metadata or null if none (optional)
     *
     * @return integer The number of bytes that were written into the file
     *
     * @throws RuntimeException on failure
     */
    public function write($key, $content, array $metadata = null)
    {
        if (!$this->zipArchive->addFromString($key, $content)) {
            // This should never happen though...
            throw new \RuntimeException(sprintf('Unable to write content to :\'%s\' file.', $key));
        }

        $this->save();

        return mb_strlen($content);
    }

    /**
     * Indicates whether the file exists
     *
     * @param  string $key
     *
     * @return boolean
     */
    public function exists($key)
    {
        return (bool) ($this->getStat($key, false));
    }

    /**
     * Returns an array of all keys matching the specified pattern
     *
     * @return array
     */
    public function keys()
    {
        $keys = array();

        for ($i = 0; $i < $this->zipArchive->numFiles; ++$i) {
            $keys[$i] = $this->zipArchive->getNameIndex($i);
        }

        return $keys;
    }

    /**
     * Returns the last modified time
     *
     * @param  string $key
     *
     * @return integer An UNIX like timestamp
     */
    public function mtime($key)
    {
        $stat = $this->getStat($key);

        return $stat['mtime'];
    }

    /**
     * Returns the checksum of the file
     *
     * @param  string $key
     *
     * @return string
     */
    public function checksum($key)
    {
        $stat = $this->getStat($key);

        return $stat['crc'];
    }

    /**
     * Deletes the file
     *
     * @param  string $key
     *
     * @throws RuntimeException on failure
     */
    public function delete($key)
    {
        if (!$this->zipArchive->deleteName($key)) {
            throw new \RuntimeException(sprintf('Unable to delete \'%s\'.', $key));
        }

        $this->save();
    }

    /**
     * Renames a file
     *
     * @param string $key
     * @param string $new
     *
     * @throws RuntimeException on failure
     */
    public function rename($key, $new)
    {
        if (!$this->zipArchive->renameName($key, $new)) {
            throw new \RuntimeException(sprintf('Unable to rename \'%s\' to \'%s\'.', $key, $new));
        }

        $this->save();
    }

    /**
     * Returns the stat of a file in the zip archive
     *  (name, index, crc, mtime, compression size, compression method, filesize)
     *
     * @param $key
     * @param bool $throwException
     * @return array|bool
     * @throws \RuntimeException
     */
    public function getStat($key, $throwException = true)
    {
        if (false === ($stat = $this->zipArchive->statName($key)) && true === $throwException) {
            throw new \RuntimeException(sprintf('Unable to stat \'%s\'.', $key));
        }

        return $stat;
    }

    /**
     * If the adapter can allow inserting metadata
     *
     * @return bool true if supports metadata, false if not
     */
    public function supportsMetadata()
    {
        return false;
    }

    public function __destruct()
    {
        $this->zipArchive->close();
        unset($this->zipArchive);
    }

    protected function initZipArchive()
    {
        $this->zipArchive = new ZipArchive();

        if (true !== ($resultCode = $this->zipArchive->open($this->zipFile, ZipArchive::CREATE))) {
            switch($resultCode) {
                case ZipArchive::ER_EXISTS:
                    $errMsg = 'File already exists';
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
                case ZipArchive::ER_SEEK;
                    $errMsg = 'Seek error.';
                    break;
                default:
                    $errMsg = 'Unknown error';
                    break;
            }

            throw new \RuntimeException(sprintf('%s', $errMsg));
        }

        return $this;
    }

    /**
     * Saves archive modifications and updates current ZipArchive instance
     *
     * @throws \RuntimeException If file could not be saved
     */
    protected function save()
    {
        // Close to save modification
        if (!$this->zipArchive->close()) {
            throw new \RuntimeException(sprintf('Unable to save ZIP archive: %s', $this->zipFile));
        }

        // Re-initialize to get updated version
            $this->initZipArchive();
    }
}
