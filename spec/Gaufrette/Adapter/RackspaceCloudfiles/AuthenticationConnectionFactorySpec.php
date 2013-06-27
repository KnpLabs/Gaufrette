<?php

namespace spec\Gaufrette\Adapter\RackspaceCloudfiles;

use PhpSpec\ObjectBehavior;

class AuthenticationConnectionFactorySpec extends ObjectBehavior
{
    /**
     * @param \CF_Authentication $authentication
     */
    function let($authentication)
    {
        $this->beConstructedWith($authentication);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Gaufrette\Adapter\RackspaceCloudfiles\AuthenticationConnectionFactory');
        $this->shouldHaveType('Gaufrette\Adapter\RackspaceCloudfiles\ConnectionFactoryInterface');
    }

    function it_creates_cf_connection($authentication)
    {
        $authentication->authenticated()->willReturn(true);
        $this->create()->shouldReturnAnInstanceOf('\CF_Connection');
    }

    function it_authenticates_when_was_not_authenticated_before($authentication)
    {
        $authentication->authenticated()->willReturn(false);
        $authentication->authenticate()->shouldBeCalled()->will(function () use ($authentication) {
            $authentication->authenticated()->willReturn(true);
        });

        $this->create()->shouldReturnAnInstanceOf('\CF_Connection');
    }
}
