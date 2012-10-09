<?php

namespace spec\Gaufrette\Adapter;

//hack - mock php built-in functions
require_once 'functions.php';

use PHPSpec2\ObjectBehavior;

class Sftp extends ObjectBehavior
{
    /**
     * @param \Ssh\Sftp $sftp
     */
    function let($sftp)
    {
        $this->beConstructedWith($sftp, '/home/l3l0');
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\Sftp');
        $this->shouldHaveType('Gaufrette\Adapter');
        $this->shouldHaveType('Gaufrette\Adapter\ChecksumCalculator');
    }

    /**
     * @param \Ssh\Sftp $sftp
     */
    function it_should_get_keys($sftp)
    {
        $sftp
            ->listDirectory('/home/l3l0', true)
            ->willReturn(array('files' => array('/home/l3l0/filename', '/home/l3l0/filename1', '/home/l3l0/aaa/filename')));

        $this->keys()->shouldReturn(array('aaa', 'aaa/filename', 'filename', 'filename1'));
    }

    /**
     * @param \Ssh\Sftp $sftp
     */
    function it_should_read_file($sftp)
    {
        $sftp
            ->read('/home/l3l0/filename')
            ->shouldBeCalled()
            ->willReturn('some content');

        $this->read('filename')->shouldReturn('some content');
    }

    /**
     * @param \Ssh\Sftp $sftp
     */
    function it_should_write_file($sftp)
    {
        $sftp
            ->write('/home/l3l0/filename', 'some content')
            ->shouldBeCalled()
            ->willReturn(12);

        $this->write('filename', 'some content')->shouldReturn(12);
    }

    /**
     * @param \Ssh\Sftp $sftp
     */
    function it_should_rename_file($sftp)
    {
        $sftp
            ->rename('/home/l3l0/filename', '/home/l3l0/filename1')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->rename('filename', 'filename1')->shouldReturn(true);
    }

    /**
     * @param \Ssh\Sftp $sftp
     */
    function it_should_check_if_file_exists($sftp)
    {
        $sftp
            ->getUrl('/home/l3l0')
            ->willReturn('ssh+ssl://localhost/home/l3l0');
        $sftp
            ->getUrl('/home/l3l0/filename')
            ->shouldBeCalled()
            ->willReturn('ssh+ssl://localhost/home/l3l0/filename');
        $sftp
            ->getUrl('/home/l3l0/filename1')
            ->shouldBeCalled()
            ->willReturn('ssh+ssl://localhost/home/l3l0/filename1');

        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename1')->shouldReturn(false);
    }
}
