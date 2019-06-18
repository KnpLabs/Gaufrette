<?php

namespace spec\Gaufrette\Adapter\AzureBlobStorage;

use PhpSpec\ObjectBehavior;

class BlobProxyFactory extends ObjectBehavior
{
    /**
     * @param string $connectionString
     */
    function let($connectionString)
    {
        $this->beConstructedWith($connectionString);
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactory');
        $this->shouldHaveType('Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactoryInterface');
    }
}
