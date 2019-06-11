<?php

namespace spec\Gaufrette\Adapter;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\Exception\StorageFailure;
use MongoDB\BSON\UTCDateTime;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;
use MongoDB\Model\BSONDocument;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class GridFSSpec extends ObjectBehavior
{
    private $resources = [];

    function let(Bucket $bucket)
    {
        $this->beConstructedWith($bucket);
    }

    function letGo()
    {
        array_map(function ($res) {
            @fclose($res);
        }, $this->resources);
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_is_checksum_calculator()
    {
        $this->shouldHaveType('Gaufrette\Adapter\ChecksumCalculator');
    }

    function it_supports_metadata()
    {
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    function it_supports_native_list_keys()
    {
        $this->shouldHaveType('Gaufrette\Adapter\ListKeysAware');
    }

    function it_reads_file($bucket)
    {
        $this->resources[] = $readable = fopen('php://memory', 'rw');
        fwrite($readable, 'some content');
        fseek($readable, 0);

        $bucket
            ->openDownloadStreamByName('filename')
            ->willReturn($readable)
        ;

        $this->read('filename')->shouldReturn('some content');
    }

    function it_fails_when_cannot_read($bucket)
    {
        $bucket->openDownloadStreamByName('filename')->willThrow(FileNotFoundException::class);

        $this->shouldThrow(FileNotFound::class)->duringRead('filename');
    }

    function it_checks_if_file_exists($bucket, BSONDocument $file)
    {
        $bucket
            ->findOne(['filename' => 'filename'])
            ->willReturn($file)
        ;
        $bucket
            ->findOne(['filename' => 'filename2'])
            ->willReturn(null)
        ;

        $this->exists('filename')->shouldReturn(true);
        $this->exists('filename2')->shouldReturn(false);
    }

    function it_deletes_file($bucket)
    {
        $bucket
            ->findOne(['filename' => 'filename'], ['projection' => ['_id' => 1]])
            ->willReturn($file = new BSONDocument(['_id' => 123]))
        ;
        $bucket->delete(123)->shouldBeCalled();

        $this->shouldNotThrow(StorageFailure::class)->duringDelete('filename');
    }

    function it_fails_when_file_to_delete_does_not_exist($bucket)
    {
        $bucket->findOne(['filename' => 'filename'], ['projection' => ['_id' => 1]])->willReturn(null);

        $this->shouldThrow(FileNotFound::class)->duringDelete('filename');
    }

    function it_writes_file($bucket)
    {
        $this->resources[] = $writable = fopen('php://memory', 'rw');

        $bucket
            ->openUploadStream('filename', ['metadata' => ['someother' => 'metadata']])
            ->willReturn($writable)
        ;

        $this->setMetadata('filename', ['someother' => 'metadata']);
        $this->shouldNotThrow(StorageFailure::class)->duringWrite('filename', 'some content');
    }

    function it_renames_file($bucket)
    {
        $this->resources[] = $writable = fopen('php://memory', 'rw');
        $this->resources[] = $readable = fopen('php://memory', 'rw');
        fwrite($readable, 'some content');
        fseek($readable, 0);

        $bucket->openUploadStream('otherFilename', ['metadata' => ['some' => 'metadata']])->willReturn($writable);
        $bucket->downloadToStreamByName('filename', $writable)->shouldBeCalled();

        $bucket
            ->findOne(['filename' => 'filename'], ['projection' => ['_id' => 1]])
            ->willReturn($toDelete = new BSONDocument(['_id' => 1234]))
        ;
        $bucket->delete(1234)->shouldBeCalled();

        $this->setMetadata('filename', ['some' => 'metadata']);
        $this->shouldNotThrow(StorageFailure::class)->duringRename('filename', 'otherFilename');
    }

    function it_fetches_keys($bucket)
    {
        $bucket
            ->find([], ['projection' => ['filename' => 1]])
            ->willReturn([new BSONDocument(['filename' => 'filename']), new BSONDocument(['filename' => 'otherFilename'])])
        ;

        $this->keys()->shouldReturn(['filename', 'otherFilename']);
    }

    function it_fetches_mtime($bucket)
    {
        $bucket
            ->findOne(['filename' => 'filename'], ['projection' => ['uploadDate' => 1]])
            ->willReturn(new BSONDocument(['uploadDate' => new UTCDateTime(12345000)]))
        ;

        $this->mtime('filename')->shouldReturn(12345);
    }

    function it_calculates_checksum($bucket)
    {
        $bucket
            ->findOne(['filename' => 'filename'], ['projection' => ['md5' => 1]])
            ->willReturn(new BSONDocument(['md5' => 'md5123']))
        ;

        $this->checksum('filename')->shouldReturn('md5123');
    }

    function it_throws_file_not_found_when_getting_the_size_of_unexisting_object($bucket)
    {
        $bucket->findOne(Argument::type('array'))->willReturn(null);

        $this->shouldThrow(FileNotFound::class)->duringSize('do-not-exists');
    }

    function it_throws_storage_failure_when_backend_does_not_specify_the_object_size($bucket)
    {
        $bucket->findOne(['filename' => 'file'], Argument::any())
            ->willReturn([])
        ;

        $this->shouldThrow(StorageFailure::class)->duringSize('file');
    }

    function it_should_retrieve_the_object_size($bucket)
    {
        $bucket->findOne(['filename' => 'file'], Argument::any())
            ->willReturn(['length' => 42])
        ;

        $this->size('file')->shouldReturn(42);
    }
}
