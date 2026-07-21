<?php

namespace spec\Gaufrette\Adapter;

use PhpSpec\ObjectBehavior;

class GoogleCloudStorageSpec extends ObjectBehavior
{
    public function let(\Google_Service_Storage $service): void
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    public function it_is_adapter(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter::class);
    }

    public function it_supports_metadata(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\MetadataSupporter::class);
    }

    public function it_is_list_keys_aware(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\ListKeysAware::class);
    }
}
