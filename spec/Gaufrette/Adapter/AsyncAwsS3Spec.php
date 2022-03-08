<?php

namespace spec\Gaufrette\Adapter;

use AsyncAws\SimpleS3\SimpleS3Client;
use Gaufrette\Adapter\MimeTypeProvider;
use PhpSpec\ObjectBehavior;

class AsyncAwsS3Spec extends ObjectBehavior
{
    /**
     * @param \AsyncAws\SimpleS3\SimpleS3Client $service
     */
    function let(SimpleS3Client $service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\AsyncAwsS3');
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
