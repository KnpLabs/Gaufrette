<?php
namespace spec\Gaufrette\Adapter;


use PhpSpec\ObjectBehavior;
use OpenCloud\Common\Exceptions\ObjFetchError;
use OpenCloud\Common\Exceptions\CreateUpdateError;
use OpenCloud\Common\Exceptions\DeleteError;

/**
 * Class LazyOpenStackCloudFilesSpec
 * @package spec\Gaufrette\Adapter
 * @author  Chris Warner <cdw.lighting@gmail.com>
 */
class LazyOpenStackCloudFilesSpec extends ObjectBehavior
{

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
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
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     * @param OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_reads_file($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $object->saveToString()->willReturn("Hello World");
        $container->dataObject("test")->willReturn($object);
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);
        $this->read('test')->shouldReturn('Hello World');
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     */
    function it_reads_file_on_error_returns_false($connectionFactory, $connection, $objectStore, $container)
    {
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);
        $container->dataObject("test")->willThrow(new ObjFetchError());
        $objectStore->container("test")->willReturn($container);

        $this->read('test')->shouldReturn(false);
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     * @param OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_writes_file_key_doesnot_exist($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $testData     = "Hello World!";
        $testDataSize = sizeof($testData);
        $object->create(array ('name' => 'test'))->willReturn(null);
        $object->setData($testData)->willReturn(null);
        $object->bytes = $testDataSize;
        $container->dataObject('test')->willReturn(false);
        $container->dataObject()->willReturn($object);
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->write('test', $testData)->shouldReturn($testDataSize);
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     * @param OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_writes_file_key_exists($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $testData      = "Hello World!";
        $testDataSize  = sizeof($testData);
        $object->bytes = $testDataSize;
        $container->dataObject('test')->willReturn($object);
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->write('test', $testData)->shouldReturn($testDataSize);
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     * @param OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_writes_file_write_fails($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $testData = "Hello World!";
        $object->create(array ('name' => 'test'))->willThrow(new CreateUpdateError());
        $object->setData($testData)->willReturn(null);
        $container->dataObject('test')->willReturn(false);
        $container->dataObject()->willReturn($object);
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->write('test', $testData)->shouldReturn(false);
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     * @param OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_key_exists_true($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $container->dataObject('test')->willReturn($object);
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->exists('test')->shouldReturn(true);
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     * @param OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_key_does_not_exist_false($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $container->dataObject('test')->willReturn($object)->willThrow(new ObjFetchError());
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->exists('test')->shouldReturn(false);
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     * @param OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_deletes_file($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $object->delete()->willReturn(null);
        $container->dataObject("test")->willReturn($object);
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->delete('test')->shouldReturn(true);
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     * @param OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_deletes_file_fails($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $object->delete()->willThrow(new DeleteError());
        $container->dataObject("test")->willReturn($object);
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->delete('test')->shouldReturn(false);
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     */
    function it_deletes_file_does_not_exist_returns_false($connectionFactory, $connection, $objectStore, $container)
    {
        $container->dataObject("test")->willThrow(new ObjFetchError());
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->delete('test')->shouldReturn(false);
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     * @param OpenCloud\ObjectStore\Resource\DataObject                        $object
     */
    function it_checksum_returns_string($connectionFactory, $connection, $objectStore, $container, $object)
    {
        $object->getETag()->willReturn("test String");
        $container->dataObject("test")->willReturn($object);
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->checksum('test')->shouldReturn("test String");
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     */
    function it_checksum_file_does_not_exist_returns_false($connectionFactory, $connection, $objectStore, $container)
    {
        $container->dataObject("test")->willThrow(new ObjFetchError());
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->checksum('test')->shouldReturn(false);
    }

    /**
     * @param Gaufrette\Adapter\OpenStackCloudFiles\ConnectionFactoryInterface $connectionFactory
     * @param OpenCloud\OpenStack                                              $connection
     * @param OpenCloud\ObjectStore\Service                                    $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container                         $container
     * @param OpenCloud\Common\Collection                                      $objectList
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
        $objectStore->container("test")->willReturn($container);
        $connection->objectStore()->willReturn($objectStore)->shouldBeCalledTimes(1);
        $connectionFactory->create()->willReturn($connection)->shouldBeCalledTimes(1);

        $this->keys()->shouldReturn($outputArray);
    }
}
