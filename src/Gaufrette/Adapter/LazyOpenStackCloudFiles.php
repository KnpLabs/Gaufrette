<?php
namespace Gaufrette\Adapter;

use Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface;
use OpenCloud\OpenStack;

/**
 * Class LazyOpenStackCloudFiles
 * @package Gaufrette\Adapter
 * @author  Chris Warner <cdw.lighting@gmail.com>
 */
class LazyOpenStackCloudFiles extends OpenCloud
{

    /**
     * @var ConnectionFactoryInterface
     */
    protected $connectionFactory;

    /**
     * @var bool
     */
    protected $connected = false;

    /**
     * @param ConnectionFactoryInterface $connectionFactory
     * @param string                     $containerName
     * @param bool                       $createContainer
     * @param bool                       $detectContentType
     */
    public function __construct($connectionFactory, $containerName, $createContainer = false, $detectContentType = true)
    {
        $this->connectionFactory = $connectionFactory;
        $this->containerName     = $containerName;
        $this->createContainer   = $createContainer;
        $this->detectContentType = $detectContentType;
    }

    /**
     * @inheritdoc
     */
    public function read($key)
    {
        $this->connect();

        return parent::read($key);
    }

    /**
     * @@inheritdoc
     */
    public function write($key, $content)
    {
        $this->connect();

        return parent::write($key, $content);
    }

    /**
     * @inheritdoc
     */
    public function exists($key)
    {
        $this->connect();

        return parent::exists($key);
    }

    /**
     * @inheritdoc
     */
    public function keys()
    {
        $this->connect();

        return parent::keys();
    }

    /**
     * @inheritdoc
     */
    public function delete($key)
    {
        $this->connect();

        return parent::delete($key);
    }

    /**
     * @inheritdoc
     */
    public function rename($sourceKey, $targetKey)
    {
        $this->connect();

        parent::rename($sourceKey, $targetKey);
    }

    /**
     * @inheritdoc
     */
    public function isDirectory($key)
    {
        $this->connect();

        return parent::isDirectory($key);
    }

    /**
     * @inheritdoc
     */
    public function checksum($key)
    {
        $this->connect();

        return parent::checksum($key);
    }

    /**
     * @inheritdoc
     */
    public function mtime($key)
    {
        $this->connect();

        return parent::mtime($key);
    }

    protected function connect()
    {
        if (!$this->connected) {
            /** @var OpenStack $connection */
            $connection = $this->connectionFactory->create();

            $this->objectStore = $connection->objectStore();
        }
    }
}
