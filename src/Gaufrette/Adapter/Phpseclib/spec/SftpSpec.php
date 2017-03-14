<?php

namespace spec\Gaufrette\Adapter\Phpseclib;

if (!defined('NET_SFTP_TYPE_REGULAR')) {
    define('NET_SFTP_TYPE_REGULAR', 1);
}

if (!defined('NET_SFTP_TYPE_DIRECTORY')) {
    define('NET_SFTP_TYPE_DIRECTORY', 2);
}

use PhpSpec\ObjectBehavior;

class SftpSpec extends ObjectBehavior
{
    function let(\phpseclib\Net\SFTP $sftp)
    {
        $this->beConstructedWith($sftp, '/home/gaufrette', false, 'gaufrette', 'password');
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_is_file_factory()
    {
        $this->shouldHaveType('Gaufrette\Adapter\FileFactory');
    }

    function it_supports_native_list_keys()
    {
        $this->shouldHaveType('Gaufrette\Adapter\ListKeysAware');
    }

    function it_fetches_keys($sftp)
    {
        $sftp
            ->rawlist('/home/gaufrette/')
            ->willReturn(array(
                'filename' => array('type' => NET_SFTP_TYPE_REGULAR),
                'filename1' => array('type' => NET_SFTP_TYPE_REGULAR),
                'aaa' => array('type' => NET_SFTP_TYPE_DIRECTORY)
            ));
        $sftp
            ->rawlist('/home/gaufrette/aaa')
            ->willReturn(array(
                'filename' => array('type' => NET_SFTP_TYPE_REGULAR),
            ));

        $this->keys()->shouldReturn(array('filename', 'filename1', 'aaa', 'aaa/filename'));
    }

    function it_reads_file($sftp)
    {
        $sftp->get('/home/gaufrette/filename')->willReturn('some content');

        $this->read('filename')->shouldReturn('some content');
    }

    function it_creates_and_writes_file($sftp)
    {
        $sftp->pwd()->willReturn('/home/gaufrette');
        $sftp->chdir('/home/gaufrette')->willReturn(true);
        $sftp->put('/home/gaufrette/filename', 'some content')->willReturn(true);
        $sftp->size('/home/gaufrette/filename')->willReturn(12);

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    function it_renames_file($sftp)
    {
        $sftp->pwd()->willReturn('/home/gaufrette');
        $sftp->chdir('/home/gaufrette')->willReturn(true);
        $sftp
            ->rename('/home/gaufrette/filename', '/home/gaufrette/filename1')
            ->willReturn(true)
        ;

        $this->rename('filename', 'filename1')->shouldReturn(true);
    }

    function it_should_check_if_file_exists($sftp)
    {
        $sftp->pwd()->willReturn('/home/gaufrette');
        $sftp->chdir('/home/gaufrette')->willReturn(true);
        $sftp->stat('/home/gaufrette/filename')->willReturn(array(
            'name' => '/home/gaufrette/filename'
        ));
        $sftp->stat('/home/gaufrette/filename1')->willReturn(false);

        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename1')->shouldReturn(false);
    }

    function it_should_check_is_directory($sftp)
    {
        $sftp->pwd()->willReturn('/home/gaufrette');
        $sftp->chdir('/home/gaufrette')->willReturn(true);
        $sftp->chdir('/home/gaufrette/aaa')->willReturn(true);
        $sftp->chdir('/home/gaufrette/filename')->willReturn(false);

        $this->isDirectory('aaa')->shouldReturn(true);
        $this->isDirectory('filename')->shouldReturn(false);
    }

    function it_should_create_file($sftp, \Gaufrette\Filesystem $filesystem)
    {
        $sftp->stat('/home/gaufrette/filename')->willReturn(array(
            'name' => '/home/gaufrette/filename',
            'size' => '30',
        ));

        $this->createFile('filename', $filesystem)->beAnInstanceOf('Gaufrette\File');
    }
}
