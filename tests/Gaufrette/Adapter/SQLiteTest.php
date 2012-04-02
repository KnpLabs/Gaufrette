<?php

namespace Gaufrette\Adapter;

require_once __DIR__.'/AbstractRDBMSTest.php';

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class SQLiteTest extends AbstractRDBMSTest
{
    protected function getAdapter()
    {
        $adapter = new \PDO('sqlite::memory:');
        $adapter->exec(<<<SQL
CREATE TABLE files (
    id INTEGER PRIMARY KEY,
    filename TEXT,
    bytes BLOB,
    metadata BLOB,
    mtime TEXT
)
SQL
);

        return new RDBMS($adapter);
    }
}
