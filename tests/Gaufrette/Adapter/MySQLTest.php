<?php

namespace Gaufrette\Adapter;

require_once __DIR__.'/AbstractRDBMSTest.php';

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class MySQLTest extends AbstractRDBMSTest
{
    protected function getAdapter()
    {
        $pdo = new \PDO('mysql://host=127.0.0.1', 'root', '');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $pdo->exec('DROP DATABASE `gaufrette_test`');
        $pdo->exec('CREATE DATABASE `gaufrette_test`');
        $pdo->exec('USE gaufrette_test');
        $pdo->exec('CREATE TABLE IF NOT EXISTS files (
            `id` INTEGER(11) AUTO_INCREMENT PRIMARY KEY,
            `filename` VARCHAR(255),
            `binary` BLOB,
            `metadata` BLOB,
            `mtime` DATETIME
        )');


        $pdo->exec('SET NAMES "UTF8"');

        return new RDBMS($pdo);
    }
}
