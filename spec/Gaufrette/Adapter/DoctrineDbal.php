<?php

namespace spec\Gaufrette\Adapter;

//hack - mock php built-in functions
require_once 'functions.php';

use PHPSpec2\ObjectBehavior;

class DoctrineDbal extends ObjectBehavior
{
    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    function let($connection)
    {
        $this->beConstructedWith($connection, 'someTableName');
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\DoctrineDbal');
        $this->shouldHaveType('Gaufrette\Adapter');
        $this->shouldHaveType('Gaufrette\Adapter\ChecksumCalculator');
    }

    function it_should_not_handle_directories()
    {
        $this->isDirectory('filename')->shouldReturn(false);
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    function it_should_check_if_file_exists($connection)
    {
        $connection
            ->quoteIdentifier(ANY_ARGUMENT)
            ->willReturnUsing(function ($argument) { return sprintf('"%s"', $argument); });

        $connection
            ->fetchColumn('SELECT COUNT("key") FROM "someTableName" WHERE "key" = :key', array('key' => 'filename'))
            ->willReturn(12);
        $this->exists('filename')->shouldReturn(true);

        $connection
            ->fetchColumn('SELECT COUNT("key") FROM "someTableName" WHERE "key" = :key', array('key' => 'filename'))
            ->willReturn(0);
        $this->exists('filename')->shouldReturn(false);
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    function it_should_write_file($connection)
    {
        $connection
            ->quoteIdentifier(ANY_ARGUMENT)
            ->willReturnUsing(function ($argument) { return sprintf('"%s"', $argument); });
        $connection
            ->fetchColumn('SELECT COUNT("key") FROM "someTableName" WHERE "key" = :key', array('key' => 'filename'))
            ->willReturn(false);
        $connection
            ->insert(
                'someTableName',
                array(
                    '"content"'  => 'some content',
                    '"mtime"'    => strtotime('2012-10-10 23:10:10'),
                    '"checksum"' => '9893532233caff98cd083a116b013c0b',
                    '"key"'      => 'filename'
                ))
            ->shouldBeCalled();

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    function it_should_update_file($connection)
    {
        $connection
            ->quoteIdentifier(ANY_ARGUMENT)
            ->willReturnUsing(function ($argument) { return sprintf('"%s"', $argument); });
        $connection
            ->fetchColumn('SELECT COUNT("key") FROM "someTableName" WHERE "key" = :key', array('key' => 'filename'))
            ->willReturn(true);
        $connection
            ->update(
                'someTableName',
                array(
                    '"content"'  => 'some content',
                    '"mtime"'    => strtotime('2012-10-10 23:10:10'),
                    '"checksum"' => '9893532233caff98cd083a116b013c0b',
                ),
                array(
                    '"key"'      => 'filename'
                ))
            ->shouldBeCalled();

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    function it_should_read_file($connection)
    {
        $connection
            ->quoteIdentifier(ANY_ARGUMENT)
            ->willReturnUsing(function ($argument) { return sprintf('"%s"', $argument); });
        $connection
            ->fetchColumn('SELECT "content" FROM "someTableName" WHERE "key" = :key', array('key' => 'filename'))
            ->willReturn('some content');

        $this->read('filename')->shouldReturn('some content');
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    function it_should_calculate_checksum($connection)
    {
        $connection
            ->quoteIdentifier(ANY_ARGUMENT)
            ->willReturnUsing(function ($argument) { return sprintf('"%s"', $argument); });
        $connection
            ->fetchColumn('SELECT "checksum" FROM "someTableName" WHERE "key" = :key', array('key' => 'filename'))
            ->willReturn(1234);

        $this->checksum('filename')->shouldReturn(1234);
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    function it_should_get_mtime($connection)
    {
        $connection
            ->quoteIdentifier(ANY_ARGUMENT)
            ->willReturnUsing(function ($argument) { return sprintf('"%s"', $argument); });
        $connection
            ->fetchColumn('SELECT "mtime" FROM "someTableName" WHERE "key" = :key', array('key' => 'filename'))
            ->willReturn(1234);

        $this->mtime('filename')->shouldReturn(1234);
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    function it_should_rename_file($connection)
    {
        $connection
            ->quoteIdentifier(ANY_ARGUMENT)
            ->willReturnUsing(function ($argument) { return sprintf('"%s"', $argument); });
        $connection
            ->update(
                'someTableName',
                array(
                    '"key"'  => 'newFile',
                ),
                array(
                    '"key"'  => 'filename'
                ))
            ->shouldBeCalled()
            ->willReturn(1);

        $this->rename('filename', 'newFile')->shouldReturn(true);
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     * @param \Doctrine\DBAL\Statement $stmt
     */
    function it_should_get_keys($connection, $stmt)
    {
        $stmt->fetchAll(\PDO::FETCH_COLUMN)->willReturn(array('filename', 'filename1', 'filename2'));
        $connection
            ->quoteIdentifier(ANY_ARGUMENT)
            ->willReturnUsing(function ($argument) { return sprintf('"%s"', $argument); });
        $connection
            ->executeQuery('SELECT "key" FROM "someTableName"')
            ->willReturn($stmt);

        $this->keys()->shouldReturn(array('filename', 'filename1', 'filename2'));
    }

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    function it_should_delete_file($connection)
    {
        $connection
            ->quoteIdentifier(ANY_ARGUMENT)
            ->willReturnUsing(function ($argument) { return sprintf('"%s"', $argument); });
        $connection
            ->delete('someTableName', array('"key"' => 'filename'))
            ->shouldBeCalled()
            ->willReturn(1);

        $this->delete('filename')->shouldReturn(true);
    }
}
