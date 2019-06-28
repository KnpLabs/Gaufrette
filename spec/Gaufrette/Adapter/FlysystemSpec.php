<?php

namespace spec\Gaufrette\Adapter;

use PhpSpec\ObjectBehavior;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\FileNotFoundException;

class FlysystemSpec extends ObjectBehavior
{
    function let(AdapterInterface $adapter, Config $config)
    {
        $this->beConstructedWith($adapter, $config);
    }

    function it_is_adapter()
    {
        $this->shouldImplement('Gaufrette\Adapter');
    }

    function it_is_list_keys_aware()
    {
        $this->shouldImplement('Gaufrette\Adapter\ListKeysAware');
    }

    function it_reads_file(AdapterInterface $adapter)
    {
        $adapter->read('filename')->willReturn(['contents' => 'Hello.']);

        $this->read('filename')->shouldReturn('Hello.');
    }

    function it_throws_file_not_found_exception_when_trying_to_read_an_unexisting_file(AdapterInterface $adapter)
    {
        $adapter->read('filename')->willThrow(new FileNotFoundException('filename'));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringread('filename');
    }

    function it_turns_exception_into_storage_failure_while_reading_a_file(AdapterInterface $adapter)
    {
        $adapter->read('filename')->willThrow(new \Exception('filename'));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringread('filename');
    }

    function it_throws_storage_failure_when_the_adapter_returns_an_error_value_when_reading_file(AdapterInterface $adapter)
    {
        $adapter->read('filename')->willReturn(false);

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringread('filename');
    }

    function it_writes_file(AdapterInterface $adapter, Config $config)
    {
        $adapter->write('filename', 'Hello.', $config)->willReturn([]);

        $this->write('filename', 'Hello.')->shouldReturn(null);
    }

    function it_turns_exception_into_storage_failure_while_writing_a_file(AdapterInterface $adapter, Config $config)
    {
        $adapter->write('filename', 'Hello.', $config)->willThrow(new \Exception('filename'));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringwrite('filename', 'Hello.');
    }

    function it_throws_storage_failure_when_the_adapter_returns_an_error_value_when_writing_file(AdapterInterface $adapter, Config $config)
    {
        $adapter->write('filename', 'Hello.', $config)->willReturn(false);

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringwrite('filename', 'Hello.');
    }

    function it_checks_if_file_exists(AdapterInterface $adapter)
    {
        $adapter->has('filename')->willReturn(true);

        $this->exists('filename')->shouldReturn(true);
    }

    function it_checks_if_file_exists_when_flysystem_returns_array(AdapterInterface $adapter)
    {
        $adapter->has('filename')->willReturn(['type' => 'file']);

        $this->exists('filename')->shouldReturn(true);
    }

    function it_turns_exception_into_storage_failure_while_checking_if_file_exists(AdapterInterface $adapter)
    {
        $adapter->has('filename')->willThrow(new \Exception('filename'));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringexists('filename');
    }

    function it_fetches_keys(AdapterInterface $adapter)
    {
        $adapter->listContents()->willReturn([[
            'path' => 'folder',
            'timestamp' => 1457104978,
            'size' => 22,
            'type' => 'dir',
        ]]);

        $this->keys()->shouldReturn(['folder']);
    }

    function it_turns_exception_into_storage_failure_while_fetching_keys(AdapterInterface $adapter)
    {
        $adapter->listContents()->willThrow(new \Exception('contents'));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringkeys();
    }

    function it_lists_keys(AdapterInterface $adapter)
    {
        $adapter->listContents()->willReturn([[
            'path' => 'folder',
            'timestamp' => 1457104978,
            'size' => 22,
            'type' => 'dir',
        ]]);

        $this->listKeys()->shouldReturn([
            'keys' => [],
            'dirs' => ['folder'],
        ]);
    }

    function it_turns_exception_into_storage_failure_while_listing_keys(AdapterInterface $adapter)
    {
        $adapter->listContents()->willThrow(new \Exception('contents'));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringlistKeys();
    }

    function it_fetches_mtime(AdapterInterface $adapter)
    {
        $adapter->getTimestamp('filename')->willReturn(1457104978);

        $this->mtime('filename')->shouldReturn(1457104978);
    }

    function it_throws_file_not_found_exception_when_trying_to_fetch_the_mtime_of_an_unexisting_file(AdapterInterface $adapter)
    {
        $adapter->getTimestamp('filename')->willThrow(new FileNotFoundException('filename'));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringmtime('filename');
    }

    function it_turns_exception_into_storage_failure_while_getting_file_mtime(AdapterInterface $adapter)
    {
        $adapter->getTimestamp('filename')->willThrow(new \Exception('filename'));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringmtime('filename');
    }

    function it_throws_storage_failure_when_the_adapter_returns_an_error_value_when_getting_file_mtime(AdapterInterface $adapter)
    {
        $adapter->getTimestamp('filename')->willReturn(false);

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringmtime('filename');
    }

    function it_deletes_file(AdapterInterface $adapter)
    {
        $adapter->delete('filename')->willReturn(true);

        $this->delete('filename')->shouldReturn(null);
    }

    function it_throws_file_not_found_exception_when_trying_to_delete_an_unexisting_file(AdapterInterface $adapter)
    {
        $adapter->delete('filename')->willThrow(new FileNotFoundException('filename'));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringdelete('filename');
    }

    function it_turns_exception_into_storage_failure_while_deleting_a_file(AdapterInterface $adapter)
    {
        $adapter->delete('filename')->willThrow(new \Exception('filename'));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringdelete('filename');
    }

    function it_throws_storage_failure_when_the_adapter_returns_an_error_value_when_deleting_file(AdapterInterface $adapter)
    {
        $adapter->delete('filename')->willReturn(false);

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringdelete('filename');
    }

    function it_renames_file(AdapterInterface $adapter)
    {
        $adapter->rename('oldfilename', 'newfilename')->willReturn(true);

        $this->rename('oldfilename', 'newfilename')->shouldReturn(null);
    }

    function it_throws_file_not_found_exception_when_trying_to_rename_an_unexisting_file(AdapterInterface $adapter)
    {
        $adapter->rename('oldfilename', 'newfilename')->willThrow(new FileNotFoundException('filename'));

        $this->shouldThrow('Gaufrette\Exception\FileNotFound')->duringrename('oldfilename', 'newfilename');
    }

    function it_turns_exception_into_storage_failure_while_renaming_a_file(AdapterInterface $adapter)
    {
        $adapter->rename('oldfilename', 'newfilename')->willThrow(new \Exception('filename'));

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringrename('oldfilename', 'newfilename');
    }

    function it_throws_storage_failure_when_the_adapter_returns_an_error_value_when_renaming_file(AdapterInterface $adapter)
    {
        $adapter->rename('oldfilename', 'newfilename')->willReturn(false);

        $this->shouldThrow('Gaufrette\Exception\StorageFailure')->duringrename('oldfilename', 'newfilename');
    }

    function it_does_not_support_is_directory(AdapterInterface $adapter)
    {
        $this->shouldThrow('Gaufrette\Exception\UnsupportedAdapterMethodException')->duringisDirectory('folder');
    }
}
