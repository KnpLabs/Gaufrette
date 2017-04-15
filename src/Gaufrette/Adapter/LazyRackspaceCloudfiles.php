<?php

namespace Gaufrette\Adapter;

use \CF_Authentication;
use \CF_Connection;

use Gaufrette\Adapter\RackspaceCloudfiles;

/**
 * Rackspace cloudfiles adapter (based on the default gaufrette rackspace adapter) that issues authentication and
 * initializes the container only when needed to.
 *
 * @author  Luciano Mammino <lmammino@oryzone.com>
 */
class LazyRackspaceCloudfiles extends RackspaceCloudfiles
{
    /**
     * @var \CF_Authentication $authentication
     */
    protected $authentication;

    /**
     * @var string $containerName
     */
    protected $containerName;

    /**
     * @var bool $createContainer
     */
    protected $createContainer;

    /**
     * @var bool $initialized
     */
    protected $initialized = false;

    /**
     * Constructor.
     * Creates a new Rackspace adapter starting from a rackspace authentication instance and a container name
     *
     * @param \CF_Authentication $authentication
     * @param string             $containerName
     * @param bool               $createContainer if <code>true</code> will try to create the container if not
     *  existent. Default <code>false</code>
     */
    public function __construct(\CF_Authentication $authentication, $containerName, $createContainer = false)
    {
        $this->authentication = $authentication;
        $this->containerName = $containerName;
        $this->createContainer = $createContainer;
    }

    /**
     * {@inheritDoc}
     */
    public function read($key)
    {
        $this->initialize();

        return parent::read($key);
    }

    /**
     * {@inheritDoc}
     */
    public function write($key, $content, array $metadata = null)
    {
        $this->initialize();

        return parent::write($key, $content, $metadata);
    }

    /**
     * {@inheritDoc}
     */
    public function exists($key)
    {
        $this->initialize();

        return parent::exists($key);
    }

    /**
     * {@inheritDoc}
     */
    public function keys()
    {
        $this->initialize();

        return parent::keys();
    }

    /**
     * {@inheritDoc}
     */
    public function checksum($key)
    {
        $this->initialize();

        return parent::keys();
    }

    /**
     * {@inheritDoc}
     */
    public function delete($key)
    {
        $this->initialize();

        return parent::delete($key);
    }

    /**
     * Initializes the container
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            if (!$this->authentication->authenticated()) {
                $this->authentication->authenticate();
            }

            $conn = new \CF_Connection($this->authentication);

            if ($this->createContainer) {
                $this->container = $conn->create_container($this->containerName);
            } else {
                $this->container = $conn->get_container($this->containerName);
            }

            $this->initialized = true;
        }
    }
}
