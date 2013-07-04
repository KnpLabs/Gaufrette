<?php

namespace Gaufrette\Adapter\RackspaceCloudfiles;

use \CF_Authentication;
use \CF_Connection;

class AuthenticationConnectionFactory implements ConnectionFactoryInterface
{
    private $authentication;

    public function __construct(CF_Authentication $authentication)
    {
        $this->authentication = $authentication;
    }

    /**
     * {@inheritDoc}
     */
    public function create()
    {
        if (!$this->authentication->authenticated()) {
            $this->authentication->authenticate();
        }

        return new CF_Connection($this->authentication);
    }
}
