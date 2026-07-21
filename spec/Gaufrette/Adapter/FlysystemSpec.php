<?php

namespace spec\Gaufrette\Adapter;

use PhpSpec\ObjectBehavior;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class FlysystemSpec extends ObjectBehavior
{
    public function let(AdapterInterface $adapter, Config $config): void
    {
        $this->beConstructedWith($adapter, $config);
    }

    public function it_is_adapter(): void
    {
        $this->shouldImplement(\Gaufrette\Adapter::class);
    }

    public function it_is_list_keys_aware(): void
    {
        $this->shouldImplement(\Gaufrette\Adapter\ListKeysAware::class);
    }

    public function it_reads_file(AdapterInterface $adapter): void
    {
        $adapter->read('filename')->willReturn(['contents' => 'Hello.']);

        $this->read('filename')->shouldReturn('Hello.');
    }

    public function it_writes_file(AdapterInterface $adapter, Config $config): void
    {
        $adapter->write('filename', 'Hello.', $config)->willReturn([]);

        $this->write('filename', 'Hello.')->shouldReturn(0);
    }

    public function it_checks_if_file_exists(AdapterInterface $adapter): void
    {
        $adapter->has('filename')->willReturn(true);

        $this->exists('filename')->shouldReturn(true);
    }

    public function it_checks_if_file_exists_when_flysystem_returns_array(AdapterInterface $adapter): void
    {
        $adapter->has('filename')->willReturn(['type' => 'file']);

        $this->exists('filename')->shouldReturn(true);
    }

    public function it_fetches_keys(AdapterInterface $adapter): void
    {
        $adapter->listContents()->willReturn([[
            'path' => 'folder',
            'timestamp' => 1457104978,
            'size' => 22,
            'type' => 'dir',
        ]]);

        $this->keys()->shouldReturn(['folder']);
    }

    public function it_lists_keys(AdapterInterface $adapter): void
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

    public function it_fetches_mtime(AdapterInterface $adapter): void
    {
        $adapter->getTimestamp('filename')->willReturn(1457104978);

        $this->mtime('filename')->shouldReturn(1457104978);
    }

    public function it_deletes_file(AdapterInterface $adapter): void
    {
        $adapter->delete('filename')->willReturn(true);

        $this->delete('filename')->shouldReturn(true);
    }

    public function it_renames_file(AdapterInterface $adapter): void
    {
        $adapter->rename('oldfilename', 'newfilename')->willReturn(true);

        $this->rename('oldfilename', 'newfilename')->shouldReturn(true);
    }

    public function it_does_not_support_is_directory(): void
    {
        $this->shouldThrow(\Gaufrette\Exception\UnsupportedAdapterMethodException::class)->duringisDirectory('folder');
    }
}
