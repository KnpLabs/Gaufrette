<?php

namespace Gaufrette\Adapter;

use Doctrine\DBAL\Result;
use Gaufrette\Adapter;
use Gaufrette\Util;
use Doctrine\DBAL\Connection;

/**
 * Doctrine DBAL adapter.
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 * @author Antoine Hérault <antoine.herault@gmail.com>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class DoctrineDbal implements Adapter, ChecksumCalculator, ListKeysAware
{
    protected array $columns = [
        'key' => 'key',
        'content' => 'content',
        'mtime' => 'mtime',
        'checksum' => 'checksum',
    ];

    /**
     * @param Connection $connection The DBAL connection
     * @param string     $table      The files table
     * @param array      $columns    The column names
     */
    public function __construct(
        private Connection $connection,
        private string $table,
        array $columns = []
    ) {
        if (!class_exists(Connection::class)) {
            throw new \LogicException('You need to install package "doctrine/dbal" to use this adapter');
        }

        $this->columns = array_replace($this->columns, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function keys(): array
    {
        $stmt = $this->connection->executeQuery(sprintf(
            'SELECT %s FROM %s',
            $this->getQuotedColumn('key'),
            $this->getQuotedTable()
        ));

        if (class_exists(Result::class)) {
            // dbal 3.x
            return $stmt->fetchFirstColumn();
        }

        // BC layer for dbal 2.x
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * {@inheritdoc}
     */
    public function rename(string $sourceKey, string $targetKey): bool
    {
        return (bool) $this->connection->update(
            $this->table,
            [$this->getQuotedColumn('key') => $targetKey],
            [$this->getQuotedColumn('key') => $sourceKey]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function mtime(string $key): int|bool
    {
        return $this->getColumnValue($key, 'mtime');
    }

    /**
     * {@inheritdoc}
     */
    public function checksum(string $key): string|bool
    {
        return $this->getColumnValue($key, 'checksum');
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return (bool) $this->fetch(
            'fetchOne',
            sprintf(
                'SELECT COUNT(%s) FROM %s WHERE %s = :key',
                $this->getQuotedColumn('key'),
                $this->getQuotedTable(),
                $this->getQuotedColumn('key')
            ),
            ['key' => $key]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function read(string $key): string|bool
    {
        return $this->getColumnValue($key, 'content');
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        return (bool) $this->connection->delete(
            $this->table,
            [$this->getQuotedColumn('key') => $key]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $key, mixed $content): int|bool
    {
        $values = [
            $this->getQuotedColumn('content') => $content,
            $this->getQuotedColumn('mtime') => time(),
            $this->getQuotedColumn('checksum') => Util\Checksum::fromContent($content),
        ];

        if ($this->exists($key)) {
            $this->connection->update(
                $this->table,
                $values,
                [$this->getQuotedColumn('key') => $key]
            );
        } else {
            $values[$this->getQuotedColumn('key')] = $key;
            $this->connection->insert($this->table, $values);
        }

        return Util\Size::fromContent($content);
    }

    /**
     * {@inheritdoc}
     */
    public function isDirectory(string $key): bool
    {
        return false;
    }

    /**
     * @return mixed
     */
    private function getColumnValue(string $key, string $column)
    {
        $value = $this->fetch(
            'fetchOne',
            sprintf(
                'SELECT %s FROM %s WHERE %s = :key',
                $this->getQuotedColumn($column),
                $this->getQuotedTable(),
                $this->getQuotedColumn('key')),
            ['key' => $key]
        );

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function listKeys(string $prefix = ''): array
    {
        $prefix = trim($prefix);

        $keys = $this->fetch(
            'fetchAllAssociative',
            sprintf(
                'SELECT %s AS _key FROM %s WHERE %s LIKE :pattern',
                $this->getQuotedColumn('key'),
                $this->getQuotedTable(),
                $this->getQuotedColumn('key')
            ),
            ['pattern' => sprintf('%s%%', $prefix)]
        );

        return [
            'dirs' => [],
            'keys' => array_map(
                function ($value) {
                    return $value['_key'];
                },
                $keys
            ),
        ];
    }

    private function getQuotedTable(): string
    {
        return $this->connection->quoteIdentifier($this->table);
    }

    private function getQuotedColumn(string $column)
    {
        return $this->connection->quoteIdentifier($this->columns[$column]);
    }

    /**
     * This function ensure the compatibility with both dbal 2.x and 3.x.
     * 
     * fetchOne will be replaced with fetchColumn
     *
     * @param 'fetchOne'|'fetchAllAssociative' $method
     * @param string $dql 
     * @param array $arguments 
     * @return mixed 
     */
    private function fetch(string $method, string $dql, array $arguments): mixed
    {
        if (!method_exists(Connection::class, $method)) {
            $method = match($method) {
                'fetchAllAssociative' => 'fetchAll',
                'fetchOne' => 'fetchColumn'
            };
        }

        // @phpstan-ignore-next-line method.notFound (BC layer for dbal 2.x, guarded by method_exists() above)
        return $this->connection->$method($dql, $arguments);
    }
}
