<?php

namespace spec\Gaufrette\Adapter;

use MongoDB\BSON\UTCDateTime;
use MongoDB\GridFS\Bucket;
use MongoDB\GridFS\Exception\FileNotFoundException;
use MongoDB\Model\BSONDocument;
use PhpSpec\ObjectBehavior;

class GridFSSpec extends ObjectBehavior
{
    private array $resources = [];

    public function let(Bucket $bucket): void
    {
        $this->beConstructedWith($bucket);
    }

    public function letGo(): void
    {
        array_map(function ($res): void {
            if (is_resource($res)) {
                @fclose($res);
            }
        }, $this->resources);
    }

    public function it_is_adapter(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter::class);
    }

    public function it_is_checksum_calculator(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\ChecksumCalculator::class);
    }

    public function it_supports_metadata(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\MetadataSupporter::class);
    }

    public function it_supports_native_list_keys(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\ListKeysAware::class);
    }

    public function it_reads_file(Bucket $bucket): void
    {
        $this->resources[] = $readable = fopen('php://memory', 'rw');
        fwrite($readable, 'some content');
        fseek($readable, 0);

        $bucket
            ->openDownloadStreamByName('filename')
            ->shouldBeCalled()
            ->willReturn($readable)
        ;

        $this->read('filename')->shouldReturn('some content');
    }

    public function it_does_not_fail_when_cannot_read(Bucket $bucket): void
    {
        $bucket->openDownloadStreamByName('filename')->willThrow(FileNotFoundException::class);

        $this->read('filename')->shouldReturn(false);
    }

    public function it_checks_if_file_exists(Bucket $bucket, BSONDocument $file): void
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

    public function it_deletes_file(Bucket $bucket): void
    {
        $bucket
            ->findOne(['filename' => 'filename'], ['projection' => ['_id' => 1]])
            ->willReturn($file = new BSONDocument(['_id' => 123]))
        ;
        $bucket->delete(123)->shouldBeCalled();

        $this->delete('filename')->shouldReturn(true);
    }

    public function it_does_not_delete_file(Bucket $bucket): void
    {
        $bucket->findOne(['filename' => 'filename'], ['projection' => ['_id' => 1]])->willReturn(null);

        $this->delete('filename')->shouldReturn(false);
    }

    public function it_writes_file(Bucket $bucket): void
    {
        $this->resources[] = $writable = fopen('php://memory', 'rw');

        $bucket
            ->openUploadStream('filename', ['metadata' => ['someother' => 'metadata']])
            ->willReturn($writable)
        ;

        $this->setMetadata('filename', ['someother' => 'metadata']);
        $this
            ->write('filename', 'some content')
            ->shouldReturn(12)
        ;
    }

    public function it_renames_file(Bucket $bucket): void
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
        $this->rename('filename', 'otherFilename')->shouldReturn(true);
    }

    public function it_fetches_keys(Bucket $bucket): void
    {
        $bucket
            ->find([], ['projection' => ['filename' => 1]])
            ->willReturn([new BSONDocument(['filename' => 'filename']), new BSONDocument(['filename' => 'otherFilename'])])
        ;

        $this->keys()->shouldReturn(['filename', 'otherFilename']);
    }

    public function it_fetches_mtime(Bucket $bucket): void
    {
        $bucket
            ->findOne(['filename' => 'filename'], ['projection' => ['uploadDate' => 1]])
            ->willReturn(new BSONDocument(['uploadDate' => new UTCDateTime(12345000)]))
        ;

        $this->mtime('filename')->shouldReturn(12345);
    }

    public function it_calculates_checksum(Bucket $bucket): void
    {
        $bucket
            ->findOne(['filename' => 'filename'], ['projection' => ['md5' => 1]])
            ->willReturn(new BSONDocument(['md5' => 'md5123']))
        ;

        $this->checksum('filename')->shouldReturn('md5123');
    }
}
