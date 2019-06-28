<?php

namespace spec\Gaufrette\Adapter;

if (!defined('NET_SFTP_TYPE_REGULAR')) {
    define('NET_SFTP_TYPE_REGULAR', 1);
}

if (!defined('NET_SFTP_TYPE_DIRECTORY')) {
    define('NET_SFTP_TYPE_DIRECTORY', 2);
}

use Gaufrette\Filesystem;
use phpseclib\Net\SFTP as Base;
use PhpSpec\ObjectBehavior;

class SFTP extends Base
{
    public function __construct()
    {
    }
}

class PhpseclibSftpSpec extends ObjectBehavior
{
    /**
     * @param \spec\Gaufrette\Adapter\SFTP $sftp
     */
    function let(SFTP $sftp)
    {
        $this->beConstructedWith($sftp, '/home/l3l0', false, 'l3lo', 'password');
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

    /**
     * @param \spec\Gaufrette\Adapter\SFTP $sftp
     */
    function it_fetches_keys(SFTP $sftp)
    {
        $sftp
            ->file_exists('/home/l3l0/')
            ->willReturn(true);
        $sftp
            ->rawlist('/home/l3l0/')
            ->willReturn([
                'filename' => ['type' => NET_SFTP_TYPE_REGULAR],
                'filename1' => ['type' => NET_SFTP_TYPE_REGULAR],
                'aaa' => ['type' => NET_SFTP_TYPE_DIRECTORY],
            ]);
        $sftp
            ->file_exists('/home/l3l0/aaa')
            ->willReturn(true);
        $sftp
            ->rawlist('/home/l3l0/aaa')
            ->willReturn([
                'filename' => ['type' => NET_SFTP_TYPE_REGULAR],
            ]);

        $this->keys()->shouldReturn(['filename', 'filename1', 'aaa', 'aaa/filename']);
    }

    /**
     * @param \spec\Gaufrette\Adapter\SFTP $sftp
     */
    function it_reads_file(SFTP $sftp)
    {
        $sftp->get('/home/l3l0/filename')->willReturn('some content');

        $this->read('filename')->shouldReturn('some content');
    }

    /**
     * @param \spec\Gaufrette\Adapter\SFTP $sftp
     */
    function it_creates_and_writes_file(SFTP $sftp)
    {
        $sftp->pwd()->willReturn('/home/l3l0');
        $sftp->chdir('/home/l3l0')->willReturn(true);
        $sftp->put('/home/l3l0/filename', 'some content')->willReturn(true);
        $sftp->size('/home/l3l0/filename')->willReturn(12);

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \spec\Gaufrette\Adapter\SFTP $sftp
     */
    function it_renames_file(SFTP $sftp)
    {
        $sftp->pwd()->willReturn('/home/l3l0');
        $sftp->chdir('/home/l3l0')->willReturn(true);
        $sftp
            ->rename('/home/l3l0/filename', '/home/l3l0/filename1')
            ->willReturn(true)
        ;

        $this->rename('filename', 'filename1')->shouldReturn(true);
    }

    /**
     * @param \spec\Gaufrette\Adapter\SFTP $sftp
     */
    function it_should_check_if_file_exists(SFTP $sftp)
    {
        $sftp->pwd()->willReturn('/home/l3l0');
        $sftp->chdir('/home/l3l0')->willReturn(true);
        $sftp->stat('/home/l3l0/filename')->willReturn([
            'name' => '/home/l3l0/filename',
        ]);
        $sftp->stat('/home/l3l0/filename1')->willReturn(false);

        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename1')->shouldReturn(false);
    }

    /**
     * @param \spec\Gaufrette\Adapter\SFTP $sftp
     */
    function it_should_check_is_directory(SFTP $sftp)
    {
        $sftp->pwd()->willReturn('/home/l3l0');
        $sftp->chdir('/home/l3l0')->willReturn(true);
        $sftp->chdir('/home/l3l0/aaa')->willReturn(true);
        $sftp->chdir('/home/l3l0/filename')->willReturn(false);

        $this->isDirectory('aaa')->shouldReturn(true);
        $this->isDirectory('filename')->shouldReturn(false);
    }

    /**
     * @param \spec\Gaufrette\Adapter\SFTP $sftp
     * @param \Gaufrette\Filesystem $filesystem
     */
    function it_should_create_file(SFTP $sftp, Filesystem $filesystem)
    {
        $sftp->stat('/home/l3l0/filename')->willReturn([
            'name' => '/home/l3l0/filename',
            'size' => '30',
        ]);

        $this->createFile('filename', $filesystem)->beAnInstanceOf('Gaufrette\File');
    }
}
