<?php

namespace spec\Gaufrette\Adapter\Azure;

use PhpSpec\ObjectBehavior;

class BlobProxyFactorySpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith('connectionString');
    }

    public function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\Azure\BlobProxyFactory');
        $this->shouldHaveType('Gaufrette\Adapter\Azure\BlobProxyFactoryInterface');
    }
}
