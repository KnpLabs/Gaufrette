<?php

namespace Gaufrette\Functional\Adapter;

use Doctrine\DBAL\DriverManager;
use Gaufrette\Adapter\DoctrineDbal;
use Gaufrette\Filesystem;

class DoctrineDbalTest extends FunctionalTestCase
{
    /** @var  \Doctrine\DBAL\Connection */
    private $connection;

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(DriverManager::class)) {
            self::markTestSkipped('Package doctrine/dbal is not installed');
        }
        parent::setUpBeforeClass();
    }

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);

        $schema = $this->connection->getSchemaManager()->createSchema();

        $table = $schema->createTable('gaufrette');
        $column = $table->addColumn('key', 'string');
        if (method_exists($column, 'setPlatformOption')) {
            // dbal 3.4+
            $column->setPlatformOption('unique', true);
        } else {
            // BC layer dbal 2.x
            $column->setUnique(true);
        }
        $table->addColumn('content', 'blob');
        $table->addColumn('mtime', 'integer');
        $table->addColumn('checksum', 'string', ['length' => 32]);

        // Generates the SQL from the defined schema and execute each line
        array_map([$this->connection, 'exec'], $schema->toSql($this->connection->getDatabasePlatform()));

        $this->filesystem = new Filesystem(new DoctrineDbal($this->connection, 'gaufrette'));
    }

    protected function tearDown(): void
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (in_array('gaufrette', $schemaManager->listTableNames())) {
            $schemaManager->dropTable('gaufrette');
        }
    }

    /**
     * @test
     */
    public function shouldListKeys(): void
    {
        $this->filesystem->write('foo/foobar/bar.txt', 'data');
        $this->filesystem->write('foo/bar/buzz.txt', 'data');
        $this->filesystem->write('foobarbuz.txt', 'data');
        $this->filesystem->write('foo', 'data');

        $allKeys = $this->filesystem->listKeys(' ');
        //empty pattern results in ->keys call
        $this->assertEquals(
            $this->filesystem->keys(),
            $allKeys['keys']
        );

        //these values are canonicalized to avoid wrong order or keys issue

        $keys = $this->filesystem->listKeys('foo');
        $this->assertEqualsCanonicalizing(
            $this->filesystem->keys(),
            $keys['keys']
        );

        $keys = $this->filesystem->listKeys('foo/foob');
        $this->assertEqualsCanonicalizing(
            ['foo/foobar/bar.txt'],
            $keys['keys']
        );

        $keys = $this->filesystem->listKeys('foo/');
        $this->assertEqualsCanonicalizing(
            ['foo/foobar/bar.txt', 'foo/bar/buzz.txt'],
            $keys['keys']
        );

        $keys = $this->filesystem->listKeys('foo');
        $this->assertEqualsCanonicalizing(
            ['foo/foobar/bar.txt', 'foo/bar/buzz.txt', 'foobarbuz.txt', 'foo'],
            $keys['keys']
        );

        $keys = $this->filesystem->listKeys('fooz');
        $this->assertEqualsCanonicalizing(
            [],
            $keys['keys']
        );
    }
}
