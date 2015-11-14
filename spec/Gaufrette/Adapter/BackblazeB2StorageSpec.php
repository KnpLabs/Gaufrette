<?php

namespace spec\Gaufrette\Adapter;

use PhpSpec\ObjectBehavior;

class BackblazeB2StorageSpec extends ObjectBehavior
{
    public function let(\B2Backblaze\B2Service $service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\BackblazeB2Storage');
    }

    public function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }
}
