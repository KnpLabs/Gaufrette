<?php

namespace spec\Gaufrette\Adapter;

//hack - mock php built-in functions
require_once 'functions.php';

use PhpSpec\ObjectBehavior;

class LocalSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('/home/somedir');
    }

    function letgo()
    {
        global $iteratorToArray;
        $iteratorToArray = array();
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_is_checksum_calculator()
    {
        $this->shouldHaveType('Gaufrette\Adapter\ChecksumCalculator');
    }

    function it_is_stream_factory()
    {
        $this->shouldHaveType('Gaufrette\Adapter\StreamFactory');
    }

    function it_reads_file()
    {
        $this->read('filename')->shouldReturn('/home/somedir/filename content');
    }

    function it_writes_file()
    {
        $this->write('filename', 'some content')->shouldReturn(12);
    }

    function it_renames_file()
    {
        $this->rename('filename', 'aaa/filename2')->shouldReturn('/home/somedir/filename to /home/somedir/aaa/filename2');
    }

    function it_checks_if_file_exists()
    {
        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename1')->shouldReturn(false);
    }

    function it_fetches_keys()
    {
        global $iteratorToArray;
        $iteratorToArray = array('/home/somedir/filename', '/home/somedir/filename1', '/home/somedir/aaa/filename');

        $this->keys()->shouldReturn(array('aaa', 'aaa/filename', 'filename', 'filename1'));
    }

    function it_fetches_mtime()
    {
        $this->mtime('filename')->shouldReturn(12345);
    }

    function it_deletes_file()
    {
        $this->delete('filename')->shouldReturn(true);
        $this->delete('filename1')->shouldReturn(false);
    }

    function it_checks_if_given_key_is_directory()
    {
        $this->isDirectory('dir')->shouldReturn(true);
        $this->isDirectory('filename')->shouldReturn(false);
    }

    function it_creates_local_stream()
    {
        $this->createStream('filename')->shouldReturnAnInstanceOf('Gaufrette\Stream\Local');
    }

    function it_allows_to_work_with_symbolic_links()
    {
        $this->beConstructedWith('symbolicLink');

        $this->read('filename')->shouldReturn('/home/somedir/filename content');
    }

    function it_does_not_allow_to_read_path_above_main_file_directory()
    {
        $this
            ->shouldThrow(new \OutOfBoundsException('The path "/home/filename" is out of the filesystem.'))
            ->duringRead('../filename')
        ;
        $this
            ->shouldThrow(new \OutOfBoundsException('The path "/home/filename" is out of the filesystem.'))
            ->duringExists('../filename')
        ;
    }

    function it_fails_when_directory_does_not_exists()
    {
        $this->beConstructedWith('/home/other');

        $this
            ->shouldThrow(new \RuntimeException('The directory "/home/other" does not exist.'))
            ->duringRead('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The directory "/home/other" does not exist.'))
            ->duringWrite('filename', 'some content')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The directory "/home/other" does not exist.'))
            ->duringRename('filename', 'otherFilename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The directory "/home/other" does not exist.'))
            ->duringExists('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The directory "/home/other" does not exist.'))
            ->duringKeys()
        ;
        $this
            ->shouldThrow(new \RuntimeException('The directory "/home/other" does not exist.'))
            ->duringMtime('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The directory "/home/other" does not exist.'))
            ->duringDelete('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The directory "/home/other" does not exist.'))
            ->duringIsDirectory('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The directory "/home/other" does not exist.'))
            ->duringCreateStream('filename')
        ;
        $this
            ->shouldThrow(new \RuntimeException('The directory "/home/other" does not exist.'))
            ->duringChecksum('filename')
        ;
    }

    function it_creates_directory_when_does_not_exists()
    {
        $this->beConstructedWith('/home/other', true);

        $this->read('filename')->shouldReturn('/home/other/filename content');
    }
}
