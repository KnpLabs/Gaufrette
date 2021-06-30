<?php

namespace spec\Gaufrette\Adapter;

use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use PhpSpec\ObjectBehavior;

class FlysystemV2Spec extends ObjectBehavior
{
    function let(\League\Flysystem\FilesystemAdapter $adapter, \League\Flysystem\Config $config)
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

    function it_reads_file(\League\Flysystem\FilesystemAdapter $adapter)
    {
        $adapter->read('filename')->willReturn('Hello.');
        $this->read('filename')->shouldReturn('Hello.');
    }

    function it_writes_file(\League\Flysystem\FilesystemAdapter $adapter, \League\Flysystem\Config $config)
    {
        $this->shouldNotThrow('League\Flysystem\UnableToWriteFile')->duringWrite('filename', 'Hello.', $config);
        $adapter->fileSize('filename')->willReturn(new FileAttributes('filename', 100));
        $adapter->write('filename', 'Hello.', $config)->shouldBeCalled();

        $this->write('filename', 'Hello.')->shouldReturn(100);
    }

    function it_checks_if_file_exists(\League\Flysystem\FilesystemAdapter $adapter)
    {
        $adapter->fileExists('filename')->willReturn(true);

        $this->exists('filename')->shouldReturn(true);
    }

    function it_fetches_keys(\League\Flysystem\FilesystemAdapter $adapter)
    {
        $adapter->listContents('', true)->willReturn(
            yield new DirectoryAttributes('folder', null, 1457104978)
        );

        $this->keys()->shouldReturn(['folder']);
    }

    function it_lists_keys(\League\Flysystem\FilesystemAdapter $adapter)
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

    function it_fetches_mtime(\League\Flysystem\FilesystemAdapter $adapter)
    {
        $adapter->lastModified('filename')->willReturn(new FileAttributes('filename', null, null, 1457104978));

        $this->mtime('filename')->shouldReturn(1457104978);
    }

    function it_deletes_file(\League\Flysystem\FilesystemAdapter $adapter)
    {
        $this->shouldNotThrow('League\Flysystem\UnableToDeleteFile')->duringDelete('filename');
        $adapter->delete('filename')->shouldBeCalled();

        $this->delete('filename')->shouldReturn(true);
    }

    function it_renames_file(\League\Flysystem\FilesystemAdapter $adapter, \League\Flysystem\Config $config)
    {
        $this->shouldNotThrow('League\Flysystem\UnableToMoveFile')->duringRename('oldfilename', 'newfilename');
        $adapter->move('oldfilename', 'newfilename', $config)->shouldBeCalled();

        $this->rename('oldfilename', 'newfilename')->shouldReturn(true);
    }

    function it_does_not_support_is_directory(\League\Flysystem\FilesystemAdapter $adapter)
    {
        $this->shouldThrow('Gaufrette\Exception\UnsupportedAdapterMethodException')->duringisDirectory('folder');
    }
}
