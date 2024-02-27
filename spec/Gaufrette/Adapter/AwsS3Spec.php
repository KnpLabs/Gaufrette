<?php

namespace spec\Gaufrette\Adapter;

use Aws\Result;
use Aws\S3\S3Client;
use Gaufrette\Adapter;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Adapter\MimeTypeProvider;
use Gaufrette\Adapter\SizeCalculator;
use PhpSpec\ObjectBehavior;

class AwsS3Spec extends ObjectBehavior
{
    function let(S3Client $service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(AwsS3::class);
    }

    function it_is_adapter()
    {
        $this->shouldHaveType(Adapter::class);
    }

    function it_supports_metadata()
    {
        $this->shouldHaveType(MetadataSupporter::class);
    }

    function it_supports_sizecalculator()
    {
        $this->shouldHaveType(SizeCalculator::class);
    }

    function it_provides_mime_type()
    {
        $this->shouldHaveType(MimeTypeProvider::class);
    }

    function it_creates_bucket_if_it_does_not_exists(S3Client $service)
    {
        $this->beConstructedWith(
            $service,
            'bucketName',
            ['create' => true]
        );

        $service
            ->doesBucketExist('bucketName')
            ->shouldBeCalledTimes(1)
            ->willReturn(false)
        ;

        $service
            ->getRegion()
            ->shouldBeCalledTimes(1)
            ->willReturn('eu-west-3')
        ;

        $service
            ->createBucket(
                [
                    'Bucket' => 'bucketName',
                    'LocationConstraint' => 'eu-west-3',
                ]
            )
            ->shouldBeCalledTimes(1)
            ->willReturn(new Result)
        ;

        $service
            ->getIterator('ListObjects', ['Bucket' => 'bucketName'])
            ->shouldBeCalledTimes(1)
            ->willReturn([])
        ;

        $this->listKeys();
    }
}
