<?php

namespace spec\Gaufrette\Adapter\AzureBlobStorage;

use PhpSpec\ObjectBehavior;

class BlobProxyFactory extends ObjectBehavior
{
    public function let(string $connectionString): void
    {
        $this->beConstructedWith($connectionString);
    }

    public function it_should_be_initializable(): void
    {
        $this->shouldHaveType(\Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactory::class);
        $this->shouldHaveType(\Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactoryInterface::class);
    }
}
