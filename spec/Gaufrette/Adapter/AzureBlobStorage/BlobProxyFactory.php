<?php

namespace spec\Gaufrette\Adapter\AzureBlobStorage;

use PhpSpec\ObjectBehavior;

class BlobProxyFactory extends ObjectBehavior
{
    public function let(string $connectionString)
    {
        $this->beConstructedWith($connectionString);
    }

    public function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactory');
        $this->shouldHaveType('Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactoryInterface');
    }
}
