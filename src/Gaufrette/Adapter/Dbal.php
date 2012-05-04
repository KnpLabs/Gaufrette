<?php

namespace Gaufrette\Adapter;

use Doctrine\DBAL\Connection;

/**
 * Dbal adapter
 *
 * @package Gaufrette
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class Dbal extends Base
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * @var array
     */
    protected $dbOptions;

    /**
     * Constructor
     *
     * @param \Doctrine\DBAL\Connection $conn
     * @param array $dbOptions
     */
    public function __construct(Connection $conn, array $dbOptions = array())
    {
        $this->conn = $conn;
        $this->dbOptions = array_merge(array(
            'table_name'      => 'gaufrette',
            'key_column'      => 'filename',
            'binary_column'   => 'content',
            'mtime_column'    => 'mtime',
            'checksum_column' => 'checksum',
        ), $dbOptions);
    }

    /**
     * Returns an array of all keys matching the specified pattern
     *
     * @return array
     */
    public function keys()
    {
        $tableName = $this->dbOptions['table_name'];
        $keyColumn = $this->dbOptions['key_column'];

        $keys = array();
        foreach ($this->conn->query("SELECT {$keyColumn} FROM {$tableName}")->fetchAll(\PDO::FETCH_NUM) as $row) {
            $keys[] = $row[0];
        }
        return $keys;
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
        $tableName = $this->dbOptions['table_name'];
        $keyColumn = $this->dbOptions['key_column'];

        $this->conn->update(
            $tableName,
            array($keyColumn => $new),
            array($keyColumn => $key)
        );
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
        $tableName   = $this->dbOptions['table_name'];
        $keyColumn   = $this->dbOptions['key_column'];
        $mtimeColumn = $this->dbOptions['mtime_column'];

        $qb = $this->conn->createQueryBuilder();
        $qb->select('f.'.$mtimeColumn)->from($tableName, 'f')
           ->where("f.{$keyColumn} = ?")
           ->setParameter(0, $key)
           ->setMaxResults(1);

        return $qb->execute()->fetchColumn();
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
        $tableName      = $this->dbOptions['table_name'];
        $keyColumn      = $this->dbOptions['key_column'];
        $checksumColumn = $this->dbOptions['checksum_column'];

        $qb = $this->conn->createQueryBuilder();
        $qb->select('f.'.$checksumColumn)
            ->from($tableName, 'f')
            ->where("f.{$keyColumn} = ?")
            ->setParameter(0, $key)
            ->setMaxResults(1);

        return $qb->execute()->fetchColumn();
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
        $tableName    = $this->dbOptions['table_name'];
        $keyColumn    = $this->dbOptions['key_column'];

        $qb = $this->conn->createQueryBuilder();
        $qb->select("COUNT(f.{$keyColumn})")
            ->from($tableName, 'f')
            ->where("f.{$keyColumn} = ?")
            ->setParameter(0, $key);

        return $qb->execute()->fetchColumn() > 0;
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
        $tableName    = $this->dbOptions['table_name'];
        $keyColumn    = $this->dbOptions['key_column'];
        $binaryColumn = $this->dbOptions['binary_column'];

        $qb = $this->conn->createQueryBuilder();
        $qb->select('f.'.$binaryColumn)
            ->from($tableName, 'f')
            ->where("f.{$keyColumn} = ?")
            ->setParameter(0, $key)
            ->setMaxResults(1);

        return $qb->execute()->fetchColumn();
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
        $tableName = $this->dbOptions['table_name'];
        $keyColumn = $this->dbOptions['key_column'];

        return $this->conn->delete($tableName, array($keyColumn => $key));
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
        $tableName      = $this->dbOptions['table_name'];
        $keyColumn      = $this->dbOptions['key_column'];
        $binaryColumn   = $this->dbOptions['binary_column'];
        $mtimeColumn    = $this->dbOptions['mtime_column'];
        $checksumColumn = $this->dbOptions['checksum_column'];

        if ($this->exists($key)) {
            $this->conn->update($tableName, array(
                $keyColumn => $key,
                $binaryColumn => $content,
                $mtimeColumn => time(),
                $checksumColumn => md5($content)
            ), array($keyColumn => $key));
        } else {
            $this->conn->insert($tableName, array(
                $keyColumn => $key,
                $binaryColumn => $content,
                $mtimeColumn => time(),
                $checksumColumn => md5($content)
            ));
        }

        return strlen($content);
    }


}
