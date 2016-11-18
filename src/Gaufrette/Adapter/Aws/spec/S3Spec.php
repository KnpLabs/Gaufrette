<?php

namespace spec\Gaufrette\Adapter\Aws;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class S3Spec extends ObjectBehavior
{
    function let(\Aws\S3\S3Client $service)
    {
        $this->beConstructedWith($service, 'bucketName');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\Aws\S3');
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
}
