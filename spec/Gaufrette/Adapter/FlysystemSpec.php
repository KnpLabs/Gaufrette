<?php

namespace spec\Gaufrette\Adapter;

use PhpSpec\ObjectBehavior;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

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

    function it_writes_file(AdapterInterface $adapter, Config $config)
    {
        $adapter->write('filename', 'Hello.', $config)->willReturn([]);

        $this->write('filename', 'Hello.')->shouldReturn([]);
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

    function it_fetches_mtime(AdapterInterface $adapter)
    {
        $adapter->getTimestamp('filename')->willReturn(1457104978);

        $this->mtime('filename')->shouldReturn(1457104978);
    }

    function it_deletes_file(AdapterInterface $adapter)
    {
        $adapter->delete('filename')->willReturn(true);

        $this->delete('filename')->shouldReturn(true);
    }

    function it_renames_file(AdapterInterface $adapter)
    {
        $adapter->rename('oldfilename', 'newfilename')->willReturn(true);

        $this->rename('oldfilename', 'newfilename')->shouldReturn(true);
    }

    function it_does_not_support_is_directory(AdapterInterface $adapter)
    {
        $this->shouldThrow('Gaufrette\Exception\UnsupportedAdapterMethodException')->duringisDirectory('folder');
    }
}
