<?php

namespace Gaufrette\Adapter;

/**
 * MySQL adapter
 *
 * @package Gaufrette
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class MySQL extends Base
{
    /**
     * @var PDO
     */
    protected $adapter;

    /**
     * @var array
     */
    protected $options;

    public function __construct(\PDO $adapter, array $options = array())
    {
        $this->adapter = $adapter;
        $this->adapter->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->adapter->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
        $this->adapter->query("SET NAMES 'utf8'");

        $this->options = array_merge(array(
            'tableName'       => 'files',
            'keyColumn'       => 'key',
            'binaryColumn'    => 'binary',
            'metadataColumn'  => 'metadata',
            'updatedAtColumn' => 'updated_at',
        ), $options);
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
        $stmt = $this->adapter->prepare("SELECT f.`{$this->options['binaryColumn']}` FROM `{$this->options['tableName']}` f WHERE f.`{$this->options['keyColumn']}` = ? LIMIT 1");
        $stmt->bindParam(1, $key);
        $stmt->execute();
        $binary = $stmt->fetchColumn();
        return $binary;
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
        try {
            $stmt = $this->adapter->prepare("SELECT * FROM `{$this->options['tableName']}` f WHERE f.`{$this->options['keyColumn']}` = ?");
            $stmt->execute(array($key));
            $row = $stmt->fetch();
        } catch (\PDOException $e) {
            return false;
        }

        return false !== $row;
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
        try {
            $stmt = $this->adapter->prepare("DELETE FROM `{$this->options['tableName']}` WHERE `{$this->options['keyColumn']}` = ?");
            $stmt->bindParam(1, $key);
            $stmt->execute();
            return 1 === $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('Unable to delete file %s', $key), 0, $e);
        }
    }

    /**
     * Returns an array of all keys matching the specified pattern
     *
     * @return array
     */
    public function keys()
    {
        $query = $this->adapter->query("SELECT f.`{$this->options['keyColumn']}` FROM `{$this->options['tableName']}` f");
        $keys = array();
        foreach ($query->fetchAll() as $row) {
            $keys[] = $row->key;
        }
        return $keys;
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
        if (null === $metadata) {
            $metadata = array();
        }

        if ($this->exists($key)) {
            $stmt = $this->delete($key);
        }

        try {
            $stmt = $this->adapter->prepare("
            INSERT INTO `{$this->options['tableName']}` (
                `{$this->options['keyColumn']}`, `{$this->options['binaryColumn']}`, `{$this->options['metadataColumn']}`, `{$this->options['updatedAtColumn']}`
            ) VALUES (
              :key, :binary, :metadata, NOW()
            )");
            $stmt->bindParam('key', $key);
            $stmt->bindParam('binary', $content);
            $stmt->bindParam('metadata', serialize($metadata));
            if (false === $stmt->execute()) {
                $this->adapter->rollBack();
                throw new \RuntimeException(sprintf('Unable to save file %s', $key));
            }
            $stmt->closeCursor();
            return mb_strlen($content, 'UTF-8');
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('Unable to save file %s', $key), 0, $e);
        }
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
        $stmt = $this->adapter->prepare("SELECT MD5(f.`{$this->options['binaryColumn']}`) as checksum FROM `{$this->options['tableName']}` f WHERE f.`{$this->options['keyColumn']}` = ? LIMIT 1");
        $stmt->bindParam(1, $key);
        $stmt->execute();
        $checksum = $stmt->fetchColumn();
        return $checksum;
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
        $stmt = $this->adapter->prepare("SELECT UNIX_TIMESTAMP(`{$this->options['createdAtColumn']}`) as mtime FROM `{$this->options['tableName']}` f WHERE f.`{$this->options['keyColumn']}` = ? LIMIT 1");
        $stmt->bindParam(1, $key);
        $stmt->execute();
        $mtime = $stmt->fetchColumn();

        return false === $mtime ? false : (integer) $mtime;
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
        try {
            $stmt = $this->adapter->prepare("UPDATE `{$this->options['tableName']}` f SET f.`{$this->options['keyColumn']}` = ? WHERE f.`{$this->options['keyColumn']}` = ?");
            $stmt->execute(array($new, $key));
            return 1 === $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('Unable to rename file %s to %s', $key, $new), 0, $e);
        }
    }

    /**
     * If the adapter can allow inserting metadata
     *
     * @return bool true if supports metadata, false if not
     */
    public function supportsMetadata()
    {
        return true;
    }
}
