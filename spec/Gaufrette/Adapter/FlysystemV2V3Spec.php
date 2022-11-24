<?php

namespace spec\Gaufrette\Adapter;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use PhpSpec\ObjectBehavior;
use League\Flysystem\Config;

class FlysystemV2V3Spec extends ObjectBehavior
{
    function let(FilesystemAdapter $adapter, Config $config)
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

    function it_reads_file(FilesystemAdapter $adapter)
    {
        $adapter->read('filename')->willReturn('Hello.');

        $this->read('filename')->shouldReturn('Hello.');
    }

    function it_writes_file(FilesystemAdapter $adapter, Config $config)
    {
        $adapter->fileSize('filename')->willReturn(new FileAttributes('filename', 11));
        $adapter->write('filename', 'Hello.', $config);

        $this->write('filename', 'Hello.');
    }

    function it_checks_if_file_exists(FilesystemAdapter $adapter)
    {
        $adapter->fileExists('filename')->willReturn(true);

        $this->exists('filename')->shouldReturn(true);
    }

    function it_checks_if_file_exists_when_flysystem_returns_array(FilesystemAdapter $adapter)
    {
        $adapter->fileExists('filename')->willReturn(true);

        $this->exists('filename')->shouldReturn(true);
    }

    function it_fetches_keys(FilesystemAdapter $adapter)
    {
        $adapter->listContents('', true)->willReturn(
            yield new DirectoryAttributes('folder', null, 1457104978)
        );

        $this->keys()->shouldReturn(['folder']);
    }

    function it_lists_keys(FilesystemAdapter $adapter)
    {
        $adapter->listContents('', true)->willReturn([
                new DirectoryAttributes('folder', null, 1457104978),
                new FileAttributes('file', 22, null, 1457104978),
            ]
        );

        $this->listKeys()->shouldReturn([
            'keys' => ['file'],
            'dirs' => ['folder'],
        ]);
    }

    function it_fetches_mtime(FilesystemAdapter $adapter)
    {
        $adapter->lastModified('filename')->willReturn(new FileAttributes('filename', null, null, 1457104978));

        $this->mtime('filename')->shouldReturn(1457104978);
    }

    function it_deletes_file(FilesystemAdapter $adapter)
    {
        $adapter->delete('filename');
        $this->delete('filename');
    }

    function it_moves_file(FilesystemAdapter $adapter, Config $config)
    {
        $this->rename('oldfilename', 'newfilename')->shouldReturn(true);
    }

    function it_does_not_support_is_directory(FilesystemAdapter $adapter)
    {
        $this->shouldThrow('Gaufrette\Exception\UnsupportedAdapterMethodException')->duringisDirectory('folder');
    }
}
