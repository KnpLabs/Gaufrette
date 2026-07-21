<?php

namespace spec\Gaufrette\Adapter;

use AsyncAws\SimpleS3\SimpleS3Client;
use Gaufrette\Adapter\MimeTypeProvider;
use PhpSpec\ObjectBehavior;

class AsyncAwsS3Spec extends ObjectBehavior
{
    public function let(SimpleS3Client $service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\AsyncAwsS3');
    }

    public function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    public function it_supports_metadata()
    {
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    public function it_supports_sizecalculator()
    {
        $this->shouldHaveType('Gaufrette\Adapter\SizeCalculator');
    }

    public function it_provides_mime_type()
    {
        $this->shouldHaveType(MimeTypeProvider::class);
    }
}
