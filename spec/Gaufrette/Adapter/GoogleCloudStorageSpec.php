<?php

namespace spec\Gaufrette\Adapter;

use PhpSpec\ObjectBehavior;

class GoogleCloudStorageSpec extends ObjectBehavior
{
    function let(\Google_Service_Storage $service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    function it_supports_metadata()
    {
        $this->shouldHaveType('Gaufrette\Adapter\MetadataSupporter');
    }

    function it_is_list_keys_aware()
    {
        $this->shouldHaveType('Gaufrette\Adapter\ListKeysAware');
    }
}
