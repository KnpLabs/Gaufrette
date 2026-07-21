<?php

namespace spec\Gaufrette\Adapter;

use AsyncAws\SimpleS3\SimpleS3Client;
use Gaufrette\Adapter\MimeTypeProvider;
use PhpSpec\ObjectBehavior;

class AsyncAwsS3Spec extends ObjectBehavior
{
    public function let(SimpleS3Client $service): void
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\AsyncAwsS3::class);
    }

    public function it_is_adapter(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter::class);
    }

    public function it_supports_metadata(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\MetadataSupporter::class);
    }

    public function it_supports_sizecalculator(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\SizeCalculator::class);
    }

    public function it_provides_mime_type(): void
    {
        $this->shouldHaveType(MimeTypeProvider::class);
    }
}
