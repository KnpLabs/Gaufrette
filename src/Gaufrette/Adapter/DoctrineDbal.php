<?php

namespace Gaufrette\Adapter;

use Gaufrette\Util;
use Gaufrette\Exception;

use Doctrine\DBAL\Connection;

/**
 * Doctrine DBAL adapter
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 * @author Antoine Hérault <antoine.herault@gmail.com>
 */
class DoctrineDbal extends Base
{
    protected $connection;
    protected $table;
    protected $columns = array(
        'key'      => 'key',
        'content'  => 'content',
        'mtime'    => 'mtime',
        'checksum' => 'checksum',
    );

    /**
     * Constructor
     *
     * @param  Connection $connection The DBAL connection
     * @param  string     $table      The files table
     * @param  array      $columns    The column names
     */
    public function __construct(Connection $connection, $table, array $columns = array())
    {
        $this->connection = $connection;
        $this->table      = $table;
        $this->columns    = array_replace($this->columns, $columns);
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $keys = array();
        $stmt = $this->connection->executeQuery(sprintf(
            'SELECT %s FROM %s',
            $this->getQuotedColumn('key'),
            $this->getQuotedTable()
        ));

        while (false !== $key = $stmt->fetch(\PDO::FETCH_COLUMN)) {
            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * {@inheritDoc}
     */
    public function rename($sourceKey, $targetKey)
    {
        if ($this->exists($targetKey)) {
            throw new Exception\UnexpectedFile($targetKey);
        }

        $count = $this->connection->update(
            $this->table,
            array($this->getQuotedColumn('key') => $sourceKey),
            array($this->getQuotedColumn('key') => $targetKey)
        );

        if (0 === $count) {
            throw new Exception\FileNotFound($sourceKey);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function mtime($key)
    {
        return $this->getColumnValue($key, 'mtime');
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        return $this->getColumnValue($key, 'checksum');
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        $count = $this->connection->fetchColumn(
            sprintf(
                'SELECT COUNT(%s) FROM %s WHERE %s = :key',
                $this->getQuotedColumn('key'),
                $this->getQuotedTable(),
                $this->getQuotedColumn('key')
            ),
            array('key' => $key)
        );

        return 0 !== (int) $count;
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
        return $this->getColumnValue($key, 'content');
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $count = $this->connection->delete(
            $this->table,
            array($this->getQuotedColumn('key') => $key)
        );

        if (0 === $count) {
            throw new Exception\FileNotFound($key);
        }
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
        $values = array(
            $this->getQuotedColumn('content')  => $content,
            $this->getQuotedColumn('mtime')    => time(),
            $this->getQuotedColumn('checksum') => Util\Checksum::fromContent($content),
        );

        if ($this->exists($key)) {
            $this->connection->update(
                $this->table,
                $values,
                array($this->getQuotedColumn('key') => $key)
            );
        } else {
            $values[$this->getQuotedColumn('key')] = $key;
            $this->connection->insert($this->table, $values);
        }

        return Util\Size::fromContent($content);
    }

    private function getColumnValue($key, $column)
    {
        $value = $this->connection->fetchColumn(
            sprintf(
                'SELECT %s FROM %s WHERE %s = :key',
                $this->getQuotedColumn($column),
                $this->getQuotedTable(),
                $this->getQuotedColumn('key')
            ),
            array('key' => $key)
        );

        if (false === $value) {
            throw new Exception\FileNotFound($key);
        }

        return $value;
    }

    private function getQuotedTable()
    {
        return $this->connection->quoteIdentifier($this->table);
    }

    private function getQuotedColumn($column)
    {
        return $this->connection->quoteIdentifier($this->columns[$column]);
    }
}
