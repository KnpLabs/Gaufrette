<?php
namespace spec\Gaufrette\Adapter;

use OpenCloud\Common\Exceptions\CreateUpdateError;
use OpenCloud\Common\Exceptions\DeleteError;
use OpenCloud\Common\Exceptions\ObjFetchError;
use PhpSpec\ObjectBehavior;

/**
 * Class OpenCloudSpec
 * @package spec\Gaufrette\Adapter
 * @author  Chris Warner <cdw.lighting@gmail.com>
 */
class OpenCloudSpec extends ObjectBehavior
{
    /**
     * @param OpenCloud\ObjectStore\Service $objectStore
     */
    function let($objectStore)
    {
        $this->beConstructedWith($objectStore, 'test', false, false);
    }

    function it_is_adapter()
    {
        $this->shouldHaveType('Gaufrette\Adapter');
    }

    /**
     * @param OpenCloud\ObjectStore\Service             $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_reads_file($objectStore, $container, $object)
    {
        $object->saveToString()->willReturn("Hello World");
        $container->dataObject("test")->willReturn($object);
        $objectStore->container("test")->willReturn($container);

        $this->read('test')->shouldReturn('Hello World');
    }

    /**
     * @param OpenCloud\ObjectStore\Service            $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container $container
     */
    function it_reads_file_on_error_returns_false($objectStore, $container)
    {
        $container->dataObject("test")->willThrow(new ObjFetchError());
        $objectStore->container("test")->willReturn($container);

        $this->read('test')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Service             $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_writes_file_if_key_does_not_exist_returns_size($objectStore, $container, $object)
    {
        $testData     = "Hello World!";
        $testDataSize = sizeof($testData);
        $object->create(array ('name' => 'test'))->willReturn(null);
        $object->setData($testData)->willReturn(null);
        $object->bytes = $testDataSize;
        $container->dataObject('test')->willReturn(false);
        $container->dataObject()->willReturn($object);
        $objectStore->container("test")->willReturn($container);

        $this->write('test', $testData)->shouldReturn($testDataSize);
    }

    /**
     * @param OpenCloud\ObjectStore\Service             $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_writes_file_if_key_exists_returns_size($objectStore, $container, $object)
    {
        $testData      = "Hello World!";
        $testDataSize  = sizeof($testData);
        $object->bytes = $testDataSize;
        $container->dataObject('test')->willReturn($object);
        $objectStore->container("test")->willReturn($container);

        $this->write('test', $testData)->shouldReturn($testDataSize);
    }

    /**
     * @param OpenCloud\ObjectStore\Service             $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_writes_file_and_write_fails_returns_false($objectStore, $container, $object)
    {
        $testData = "Hello World!";
        $object->create(array ('name' => 'test'))->willThrow(new CreateUpdateError());
        $object->setData($testData)->willReturn(null);
        $container->dataObject('test')->willReturn(false);
        $container->dataObject()->willReturn($object);
        $objectStore->container("test")->willReturn($container);

        $this->write('test', $testData)->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Service             $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function if_key_exists_return_true($objectStore, $container, $object)
    {
        $container->dataObject('test')->willReturn($object);
        $objectStore->container("test")->willReturn($container);

        $this->exists('test')->shouldReturn(true);
    }

    /**
     * @param OpenCloud\ObjectStore\Service             $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function if_key_does_not_exist_return_false($objectStore, $container, $object)
    {
        $container->dataObject('test')->willReturn($object)->willThrow(new ObjFetchError());
        $objectStore->container("test")->willReturn($container);

        $this->exists('test')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Service             $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_deletes_file_on_success_returns_true($objectStore, $container, $object)
    {
        $object->delete()->willReturn(null);
        $container->dataObject("test")->willReturn($object);
        $objectStore->container("test")->willReturn($container);

        $this->delete('test')->shouldReturn(true);
    }

    /**
     * @param OpenCloud\ObjectStore\Service             $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_deletes_file_returns_false_on_failure($objectStore, $container, $object)
    {
        $object->delete()->willThrow(new DeleteError());
        $container->dataObject("test")->willReturn($object);
        $objectStore->container("test")->willReturn($container);

        $this->delete('test')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Service            $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container $container
     */
    function it_deletes_file_if_file_does_not_exist_returns_false($objectStore, $container)
    {
        $container->dataObject("test")->willThrow(new ObjFetchError());
        $objectStore->container("test")->willReturn($container);

        $this->delete('test')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Service             $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container  $container
     * @param OpenCloud\ObjectStore\Resource\DataObject $object
     */
    function it_returns_checksum_if_file_exists($objectStore, $container, $object)
    {
        $object->getETag()->willReturn("test String");
        $container->dataObject("test")->willReturn($object);
        $objectStore->container("test")->willReturn($container);

        $this->checksum('test')->shouldReturn("test String");
    }

    /**
     * @param OpenCloud\ObjectStore\Service            $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container $container
     */
    function it_returns_false_when_file_does_not_exist($objectStore, $container)
    {
        $container->dataObject("test")->willThrow(new ObjFetchError());
        $objectStore->container("test")->willReturn($container);

        $this->checksum('test')->shouldReturn(false);
    }

    /**
     * @param OpenCloud\ObjectStore\Service            $objectStore
     * @param OpenCloud\ObjectStore\Resource\Container $container
     * @param OpenCloud\Common\Collection              $objectList
     */
    function it_returns_files_as_sorted_array($objectStore, $container, $objectList)
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

        $this->keys()->shouldReturn($outputArray);
    }
}
