<?php

namespace spec\Gaufrette\Adapter;

use Gaufrette\Adapter\MimeTypeProvider;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AwsS3Spec extends ObjectBehavior
{
    /**
     * @param \Aws\S3\S3Client $service
     */
    function let($service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\AwsS3');
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_supports_metadata()
    {
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    function it_supports_sizecalculator()
    {
        $this->shouldHaveType('Gaufrette\Adapter\SizeCalculator');
    }

    function it_provides_mime_type()
    {
        $this->shouldHaveType(MimeTypeProvider::class);
    }
}
