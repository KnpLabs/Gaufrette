<?php
namespace spec\Gaufrette\Adapter;

use PhpSpec\ObjectBehavior;
use OpenCloud\Common\Exceptions\ObjFetchError;
use OpenCloud\Common\Exceptions\CreateUpdateError;
use OpenCloud\Common\Exceptions\DeleteError;
use Guzzle\Http\Exception\RequestException;

define('RAXSDK_OBJSTORE_NAME','cloudFiles');
define('RAXSDK_OBJSTORE_REGION','DFW');
define('RAXSDK_OBJSTORE_URLTYPE', 'publicURL');

/**
 * Class LazyOpenStackCloudFilesSpec
 * @package spec\Gaufrette\Adapter
 * @author  Chris Warner <cdw.lighting@gmail.com>
 */
class LazyOpenStackCloudFilesSpec extends ObjectBehavior
{
    const REGION = 'ORD';
    const CLOUD_FILES = 'cloudFiles';

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     */
    function let($connectionFactory)
    {
        $containerName     = 'test';
        $createContainer   = false;
        $detectContentType = false;

        $this->beConstructedWith($connectionFactory, $containerName, $createContainer, $detectContentType);
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     * @param \OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_reads_file($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $object->getContent()->willReturn("Hello World");
        $container->dataObject("test")->willReturn($object);
        $this->buildObjectStore($objectStore, $container);
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->read('test')->shouldReturn('Hello World');
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     */
    function it_reads_file_on_error_returns_false($connectionFactory, $connection, $objectStore, $container)
    {
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);
        $container->dataObject("test")->willThrow(new ObjFetchError());
        $this->buildObjectStore($objectStore, $container);

        $this->read('test')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     * @param \OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_writes_file_key_doesnot_exist($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $testData      = "Hello World!";
        $testDataSize  = sizeof($testData);
        $object->getContentLength()->willReturn($testDataSize);
        $container->uploadObject('test', $testData)->willReturn($object);
        $container->dataObject('test')->willReturn(false);
        $this->buildObjectStore($objectStore, $container);
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->write('test', $testData)->shouldReturn($testDataSize);
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     * @param \OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_writes_file_key_exists($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $testData      = "Hello World!";
        $testDataSize  = sizeof($testData);
        $object->getContentLength()->willReturn($testDataSize);
        $container->dataObject('test')->willReturn($object);
        $this->buildObjectStore($objectStore, $container);
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->write('test', $testData)->shouldReturn($testDataSize);
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     * @param \OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_returns_false_if_write_fails($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $testData = "Hello World!";
        $container->uploadObject('test', $testData)->willThrow(new RequestException());
        $container->dataObject('test')->willReturn(false);
        $this->buildObjectStore($objectStore, $container);
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->write('test', $testData)->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     * @param \OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function be_true_if_key_exists_true($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $container->dataObject('test')->willReturn($object);
        $this->buildObjectStore($objectStore, $container);
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->exists('test')->shouldReturn(true);
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     * @param \OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_key_does_not_exist_false($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $container->dataObject('test')->willReturn($object)->willThrow(new ObjFetchError());
        $this->buildObjectStore($objectStore, $container);
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->exists('test')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     * @param \OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_deletes_file($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $object->delete()->willReturn(null);
        $container->dataObject("test")->willReturn($object);
        $this->buildObjectStore($objectStore, $container);
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->delete('test')->shouldReturn(true);
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     * @param \OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_deletes_file_fails($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $object->delete()->willThrow(new DeleteError());
        $container->dataObject("test")->willReturn($object);
        $this->buildObjectStore($objectStore, $container);
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->delete('test')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     */
    function it_deletes_file_does_not_exist_returns_false($connectionFactory, $connection, $objectStore, $container)
    {
        $container->dataObject("test")->willThrow(new ObjFetchError());
        $this->buildObjectStore($objectStore, $container);
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->delete('test')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     * @param \OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_checksum_returns_string($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $object->getEtag()->willReturn("test String");
        $container->dataObject("test")->willReturn($object);
        $this->buildObjectStore($objectStore, $container);

        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->checksum('test')->shouldReturn("test String");
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     */
    function it_checksum_file_does_not_exist_returns_false($connectionFactory, $connection, $objectStore, $container)
    {
        $container->dataObject("test")->willThrow(new ObjFetchError());
        $this->buildObjectStore($objectStore, $container);

        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->checksum('test')->shouldReturn(false);
    }

    /**
     * @param \Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param \OpenCloud\OpenStack                                              $connection
     * @param \OpenCloud\ObjectStore\Service                                    $objectStore
     * @param \OpenCloud\ObjectStore\Resource\Container                         $container
     * @param \OpenCloud\Common\Collection                                      $objectList
     */
    function it_keys_returns_sorted_array($connectionFactory, $connection, $objectStore, $container, $objectList)
    {
        $inputArray  = array ('key5', 'key2', 'key1');
        $outputArray = $inputArray;
        sort($outputArray);
        $index = 0;

        $objectList->next()->will(
                   function () use ($inputArray, &$index) {
                       if ($index < count($inputArray)) {
                           $objectItem       = new \stdClass();
                           $objectItem->name = $inputArray[$index];
                           $index++;

                           return $objectItem;
                       }
                   }
        )          ->shouldBeCalledTimes(count($inputArray) + 1);

        $container->objectList()->willReturn($objectList);
        $this->buildObjectStore($objectStore, $container);
        $this->buildConnection($connection, $objectStore);
        $this->buildConnectionFactory($connectionFactory, $connection);

        $this->keys()->shouldReturn($outputArray);
    }

    /**
     * @param $connectionFactory
     * @param $connection
     */
    protected
    function buildConnectionFactory(
        $connectionFactory, $connection
    ) {
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);
        $connectionFactory->getRegion()->willReturn(self::REGION);
        $connectionFactory->getCatalogName()->willReturn(self::CLOUD_FILES);
    }

    /**
     * @param $connection
     * @param $objectStore
     */
    protected
    function buildConnection(
        $connection, $objectStore
    ) {
        $connection->objectStoreService(self::CLOUD_FILES, self::REGION)->willReturn($objectStore)->shouldBeCalledTimes(
                   1
        );
    }

    /**
     * @param $objectStore
     * @param $container
     */
    protected
    function buildObjectStore(
        $objectStore, $container
    ) {
        $objectStore->getContainer("test")->willReturn($container);
    }
}
