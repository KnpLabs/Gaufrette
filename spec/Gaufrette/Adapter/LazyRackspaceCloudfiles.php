<?php

namespace spec\Gaufrette\Adapter;

require_once __DIR__.'/../../../vendor/rackspace/php-cloudfiles/cloudfiles.php';

use PHPSpec2\ObjectBehavior;

class LazyRackspaceCloudfiles extends ObjectBehavior
{
    /**
     * @param \CF_Authentication $authentication
     */
    function let($authentication)
    {
        $authentication->authenticated()->willReturn(true);
        $this->beConstructedWith($authentication, 'containerName', true);
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('\Gaufrette\Adapter\LazyRackspaceCloudfiles');
        $this->shouldHaveType('\Gaufrette\Adapter\RackspaceCloudfiles');
    }


    function it_should_initialize_before_read()
    {

    }

    function it_should_initialize_before_write()
    {

    }

    function it_should_initialize_before_exists()
    {

    }

    function it_should_initialize_before_keys()
    {

    }

    function it_should_initialize_before_checksum()
    {

    }

    function it_should_initialize_before_delete()
    {

    }
}

