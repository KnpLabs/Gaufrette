<?php

namespace Gaufrette\Adapter;

use Doctrine\DBAL\DriverManager;

/**
 * Dbal testcase
 *
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class DbalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Dbal
     */
    protected $adapter;

    /**
     * @var \Doctrine\DBAL\Schema\Schema
     */
    protected $schema;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    protected function setUp()
    {
        $this->conn = DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ));

        $sm = $this->conn->getSchemaManager();
        $this->schema = $sm->createSchema();

        $table = $this->schema->createTable('gaufrette');
        $table->addColumn('filename', 'string', array('unique' => true));
        $table->addColumn('content', 'blob');
        $table->addColumn('mtime', 'integer');
        $table->addColumn('checksum', 'string', array('length' => 32));

        $queries = $this->schema->toSql($this->conn->getDatabasePlatform());
        foreach ($queries as $query) {
            $this->conn->exec($query);
        }

        $this->adapter = new Dbal($this->conn);
    }

    protected function tearDown()
    {
        $queries = $this->schema->toDropSql($this->conn->getDatabasePlatform());
        foreach ($queries as $query) {
            $this->conn->exec($query);
        }
        $this->conn->close();
    }

    public function testWrite()
    {
        $this->assertEquals(6, $this->adapter->write('foobar.txt', 'foobar'));
    }

    public function testUpdate()
    {
        $this->adapter->write('foo', 'bar');
        $this->adapter->write('foo', 'baz');

        $this->assertEquals('baz', $this->adapter->read('foo'));
        $this->assertCount(1, $this->adapter->keys());
    }

    public function testRead()
    {
        $this->adapter->write('foobar', 'foobar');
        $this->assertEquals('foobar', $this->adapter->read('foobar'));
    }

    public function testMtime()
    {
        $this->adapter->write('foo', 'bar');
        $this->assertGreaterThanOrEqual(time(), $this->adapter->mtime('foo'));
    }

    public function testChecksum()
    {
        $content = 'foobar';
        $checksum = md5($content);

        $this->adapter->write('foo', $content);
        $this->assertEquals($checksum, $this->adapter->checksum('foo'));
    }

    public function testExist()
    {
        $this->assertFalse($this->adapter->exists('foo'));
        $this->adapter->write('foo', 'bar');
        $this->assertTrue($this->adapter->exists('foo'));
    }

    public function testRename()
    {
        $this->adapter->write('foo', 'bar');
        $this->assertTrue($this->adapter->exists('foo'));

        $this->adapter->rename('foo', 'foobar');
        $this->assertFalse($this->adapter->exists('foo'));
        $this->assertTrue($this->adapter->exists('foobar'));
    }

    public function testKeys()
    {
        $this->adapter->write('foo', 'bar');
        $this->adapter->write('foobar', 'bar');

        $this->assertEquals(array('foo', 'foobar'), $this->adapter->keys());
    }
}
