<?php

namespace spec\Gaufrette\Adapter;

if (!defined('NET_SFTP_TYPE_REGULAR')) {
    define('NET_SFTP_TYPE_REGULAR', 1);
}

if (!defined('NET_SFTP_TYPE_DIRECTORY')) {
    define('NET_SFTP_TYPE_DIRECTORY', 2);
}

use PHPSpec2\ObjectBehavior;

// @todo Remove after upgrade mockery (https://github.com/padraic/mockery/issues/139).
class Net_SFTP extends \Net_SFTP
{
    public function __construct($host, $port = 22, $timeout = 10)
    {
        parent::Net_SFTP($host, $port, $timeout);
    }
}

class PhpseclibSftp extends ObjectBehavior
{
    /**
     * @param \spec\Gaufrette\Adapter\Net_SFTP $sftp
     */
    function let($sftp)
    {
        $this->beConstructedWith($sftp, '/home/l3l0');
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\PhpseclibSftp');
        $this->shouldHaveType('Gaufrette\Adapter\FileFactory');
        $this->shouldHaveType('Gaufrette\Adapter\ListKeysAware');
    }

    /**
     * @param \spec\Gaufrette\Adapter\Net_SFTP $sftp
     */
    function it_should_get_keys($sftp)
    {
        $sftp
            ->rawlist('/home/l3l0/')
            ->willReturn(array(
                'filename' => array('type' => NET_SFTP_TYPE_REGULAR),
                'filename1' => array('type' => NET_SFTP_TYPE_REGULAR),
                'aaa' => array('type' => NET_SFTP_TYPE_DIRECTORY)
            ));
        $sftp
            ->rawlist('/home/l3l0/aaa')
            ->willReturn(array(
                'filename' => array('type' => NET_SFTP_TYPE_REGULAR),
            ));

        $this->keys()->shouldReturn(array('filename', 'filename1', 'aaa', 'aaa/filename'));
    }

    /**
     * @param \spec\Gaufrette\Adapter\Net_SFTP $sftp
     */
    function it_should_read_file($sftp)
    {
        $sftp->get('/home/l3l0/filename')->willReturn('some content');

        $this->read('filename')->shouldReturn('some content');
    }

    /**
     * @param \spec\Gaufrette\Adapter\Net_SFTP $sftp
     */
    function it_should_write_file($sftp)
    {
        $sftp->put('/home/l3l0/filename', 'some content')->willReturn(true);
        $sftp->size('/home/l3l0/filename')->willReturn(12);

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \spec\Gaufrette\Adapter\Net_SFTP $sftp
     */
    function it_should_rename_file($sftp)
    {
        $sftp
            ->rename('/home/l3l0/filename', '/home/l3l0/filename1')
            ->willReturn(true);

        $this->rename('filename', 'filename1')->shouldReturn(true);
    }

    /**
     * @param \spec\Gaufrette\Adapter\Net_SFTP $sftp
     */
    function it_should_check_if_file_exists($sftp)
    {
        $sftp->stat('/home/l3l0/filename')->willReturn(array(
            'name' => '/home/l3l0/filename'
        ));
        $sftp->stat('/home/l3l0/filename1')->willReturn(false);

        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename1')->shouldReturn(false);
    }

    /**
     * @param \spec\Gaufrette\Adapter\Net_SFTP $sftp
     */
    function it_should_check_is_directory($sftp)
    {
        $sftp->pwd()->willReturn('/home/l3l0');
        $sftp->chdir('/home/l3l0')->willReturn(true);
        $sftp->chdir('/home/l3l0/aaa')->willReturn(true);
        $sftp->chdir('/home/l3l0/filename')->willReturn(false);

        $this->isDirectory('aaa')->shouldReturn(true);
        $this->isDirectory('filename')->shouldReturn(false);
    }

    /**
     * @param \spec\Gaufrette\Adapter\Net_SFTP $sftp
     * @param \Gaufrette\Filesystem $filesystem
     */
    function it_should_create_file($sftp, $filesystem)
    {
        $sftp->stat('/home/l3l0/filename')->willReturn(array(
            'name' => '/home/l3l0/filename',
            'size' => '30',
        ));

        $this->createFile('filename', $filesystem)->beAnInstanceOf('Gaufrette\File');
    }
}
