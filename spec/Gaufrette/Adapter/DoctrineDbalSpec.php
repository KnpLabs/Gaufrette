<?php

namespace spec\Gaufrette\Adapter;

//hack - mock php built-in functions
require_once 'functions.php';

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DoctrineDbalSpec extends ObjectBehavior
{
    function let(Connection $connection)
    {
        $this->beConstructedWith($connection, 'someTableName');
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_is_checksum_calculator()
    {
        $this->shouldHaveType('Gaufrette\Adapter\ChecksumCalculator');
    }

    function it_does_not_handle_directories()
    {
        $this->isDirectory('filename')->shouldReturn(false);
    }

    function it_checks_if_file_exists(Connection $connection)
    {
        $connection
            ->quoteIdentifier(Argument::any())
            ->will(function ($argument) {
                return sprintf('"%s"', $argument[0]);
            });

        $connection
            ->fetchColumn('SELECT COUNT("key") FROM "someTableName" WHERE "key" = :key', ['key' => 'filename'])
            ->willReturn(12);
        $this->exists('filename')->shouldReturn(true);

        $connection
            ->fetchColumn('SELECT COUNT("key") FROM "someTableName" WHERE "key" = :key', ['key' => 'filename'])
            ->willReturn(0);
        $this->exists('filename')->shouldReturn(false);
    }

    function it_writes_to_new_file(Connection $connection)
    {
        $connection
            ->quoteIdentifier(Argument::any())
            ->will(function ($argument) {
                return sprintf('"%s"', $argument[0]);
            });
        $connection
            ->fetchColumn('SELECT COUNT("key") FROM "someTableName" WHERE "key" = :key', ['key' => 'filename'])
            ->willReturn(false);
        $connection
            ->insert(
                'someTableName',
                [
                    '"content"' => 'some content',
                    '"mtime"' => strtotime('2012-10-10 23:10:10'),
                    '"checksum"' => '9893532233caff98cd083a116b013c0b',
                    '"key"' => 'filename',
                ])
            ->shouldBeCalled();

        $this->write('filename', 'some content');
    }

    function it_write_file(Connection $connection)
    {
        $connection
            ->quoteIdentifier(Argument::any())
            ->will(function ($argument) {
                return sprintf('"%s"', $argument[0]);
            });
        $connection
            ->fetchColumn('SELECT COUNT("key") FROM "someTableName" WHERE "key" = :key', ['key' => 'filename'])
            ->willReturn(true);
        $connection
            ->update(
                'someTableName',
                [
                    '"content"' => 'some content',
                    '"mtime"' => strtotime('2012-10-10 23:10:10'),
                    '"checksum"' => '9893532233caff98cd083a116b013c0b',
                ],
                [
                    '"key"' => 'filename',
                ])
            ->shouldBeCalled();

        $this->write('filename', 'some content');
    }

    function it_reads_file(Connection $connection)
    {
        $connection
            ->quoteIdentifier(Argument::any())
            ->will(function ($argument) {
                return sprintf('"%s"', $argument[0]);
            });
        $connection
            ->fetchColumn('SELECT "content" FROM "someTableName" WHERE "key" = :key', ['key' => 'filename'])
            ->willReturn('some content');

        $this->read('filename')->shouldReturn('some content');
    }

    function it_calculates_checksum(Connection $connection)
    {
        $connection
            ->quoteIdentifier(Argument::any())
            ->will(function ($argument) {
                return sprintf('"%s"', $argument[0]);
            });
        $connection
            ->fetchColumn('SELECT "checksum" FROM "someTableName" WHERE "key" = :key', ['key' => 'filename'])
            ->willReturn(1234);

        $this->checksum('filename')->shouldReturn(1234);
    }

    function it_gets_mtime(Connection $connection)
    {
        $connection
            ->quoteIdentifier(Argument::any())
            ->will(function ($argument) {
                return sprintf('"%s"', $argument[0]);
            });
        $connection
            ->fetchColumn('SELECT "mtime" FROM "someTableName" WHERE "key" = :key', ['key' => 'filename'])
            ->willReturn(1234);

        $this->mtime('filename')->shouldReturn(1234);
    }

    function it_renames_file(Connection $connection)
    {
        $connection
            ->quoteIdentifier(Argument::any())
            ->will(function ($argument) {
                return sprintf('"%s"', $argument[0]);
            });
        $connection
            ->update(
                'someTableName',
                [
                    '"key"' => 'newFile',
                ],
                [
                    '"key"' => 'filename',
                ])
            ->shouldBeCalled()
            ->willReturn(1);

        $this->rename('filename', 'newFile');
    }

    function it_get_keys(Connection $connection, Statement $stmt)
    {
        $stmt->fetchAll(\PDO::FETCH_COLUMN)->willReturn(['filename', 'filename1', 'filename2']);
        $connection
            ->quoteIdentifier(Argument::any())
            ->will(function ($argument) {
                return sprintf('"%s"', $argument[0]);
            });
        $connection
            ->executeQuery('SELECT "key" FROM "someTableName"')
            ->willReturn($stmt);

        $this->keys()->shouldReturn(['filename', 'filename1', 'filename2']);
    }

    function it_deletes_file(Connection $connection)
    {
        $connection
            ->quoteIdentifier(Argument::any())
            ->will(function ($argument) {
                return sprintf('"%s"', $argument[0]);
            });
        $connection
            ->delete('someTableName', ['"key"' => 'filename'])
            ->shouldBeCalled()
            ->willReturn(1);

        $this->delete('filename');
    }
}
