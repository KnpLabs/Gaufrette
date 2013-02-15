<?php

namespace spec\Gaufrette\Adapter\RackspaceCloudfiles;

use PHPSpec2\ObjectBehavior;

class AuthenticationConnectionFactory extends ObjectBehavior
{
    /**
     * @param \CF_Authentication $authentication
     */
    function let($authentication)
    {
        $this->beConstructedWith($authentication);
    }

    function it_should_be_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\RackspaceCloudfiles\AuthenticationConnectionFactory');
        $this->shouldHaveType('Gaufrette\Adapter\RackspaceCloudfiles\ConnectionFactoryInterface');
    }

    function it_should_create_cf_connection()
    {
        $this->create()->shouldReturnAnInstanceOf('\CF_Connection');
    }

    function it_should_authenticate_when_not_authenticated($authentication)
    {
        $authentication->authenticated()->willReturn(false);
        $authentication->authenticate()->shouldBeCalled();
        $authentication->authenticated()->willReturn(true);

        $this->create()->shouldReturnAnInstanceOf('\CF_Connection');
    }
}
