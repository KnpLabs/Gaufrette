<?php
/**
 * Created by PhpStorm.
 * User: cwarner
 * Date: 10/2/13
 * Time: 12:48 PM
 */

namespace Gaufrette\Unit;

use OpenCloud\Common\Exceptions\DeleteError;
use OpenCloud\Common\Exceptions\ObjFetchError;
use OpenCloud\Common\Exceptions\CreateUpdateError;

abstract class BaseOpenCloudTestCase extends \PHPUnit_Framework_TestCase {

    abstract protected function buildOpenCloudClass($objectStore, $createContainer = false, $detectContentType = true);

    public function testRead_KeyExists_ReturnsExpectedValue()
    {
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $dataObject = $this->getMock(
            'OpenCloud\OpenStore\Resource\DataObject',
            array('SaveToString'),
            array(), '', false, false, false, false
        );

        $dataObject->expects($this->once())
            ->method('SaveToString')
            ->will($this->returnValue('test'));

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->returnValue($dataObject));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);
        $result = $sut->read('test');

        $this->assertSame('test', $result);
    }

    public function testRead_KeyDoesNotExists_ReturnsFalse()
    {
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->throwException(new ObjFetchError()));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);
        $result = $sut->read('test');

        $this->assertFalse($result);

    }

    public function testWrite_keyDoesNotExist_ReturnsXbytes(){

        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $dataObject = $this->getMock(
            'OpenCloud\OpenStore\Resource\DataObject',
            array('SetData', 'Create'),
            array(), '', false, false, false, false
        );

        $content = "The quick brown fox jumps over a lazy dog";
        $data = array('name'=>'test', 'content_type' => 'text/plain');
        $dataBytes = sizeof($content);

        $dataObject->bytes = $dataBytes;

        $dataObject->expects($this->once())
            ->method('SetData')
            ->with($this->equalTo($content));

        $dataObject->expects($this->once())
            ->method('Create')
            ->with($this->equalTo($data));

        $container->expects($this->exactly(2))
            ->method('DataObject')
            ->will($this->onConsecutiveCalls(false, $dataObject));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $bytes = $sut->write('test', $content);

        $this->assertEquals($dataBytes, $bytes);


    }

    public function testWrite_keyExists_ReturnsXbytes(){
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $dataObject = $this->getMock(
            'OpenCloud\OpenStore\Resource\DataObject',
            array('SetData', 'Create'),
            array(), '', false, false, false, false
        );

        $content = "The quick brown fox jumps over a lazy dog";
        $data = array('name'=>'test', 'content_type' => 'text/plain');
        $dataBytes = sizeof($content);

        $dataObject->bytes = $dataBytes;

        $container->expects($this->once())
            ->method('DataObject')
            ->will($this->onConsecutiveCalls($dataObject));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $bytes = $sut->write('test', $content);

        $this->assertEquals($dataBytes, $bytes);
    }

    public function testWrite_updateFailed_ReturnsFalse(){
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $dataObject = $this->getMock(
            'OpenCloud\OpenStore\Resource\DataObject',
            array('SetData', 'Create'),
            array(), '', false, false, false, false
        );

        $content = "The quick brown fox jumps over a lazy dog";
        $data = array('name'=>'test', 'content_type' => 'text/plain');
        $dataBytes = sizeof($content);

        $dataObject->bytes = $dataBytes;

        $dataObject->expects($this->once())
            ->method('SetData')
            ->with($this->equalTo($content));

        $dataObject->expects($this->once())
            ->method('Create')
            ->with($this->equalTo($data))
            ->will($this->throwException(new CreateUpdateError()));

        $container->expects($this->exactly(2))
            ->method('DataObject')
            ->will($this->onConsecutiveCalls(false, $dataObject));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $bytes = $sut->write('test', $content);

        $this->assertFalse($bytes);

    }

    public function testExists_keyExists_ReturnTrue(){
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $dataObject = $this->getMock(
            'OpenCloud\OpenStore\Resource\DataObject',
            array('SaveToString'),
            array(), '', false, false, false, false
        );

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->returnValue($dataObject));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertTrue($sut->exists('test'));

    }

    public function testExists_keyDoesNotExist_ReturnFalse(){
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->throwException(new ObjFetchError()));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertFalse($sut->exists('test'));
    }

    public function testKeys_hasKeys_ReturnsSortedArray(){

        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('ObjectList'),
            array(), '', false, false, false, false);

        $objectList = $this->getMock(
            'OpenCloud\Common\Collection',
            array('next'),
            array(), '', false, false, false, false
        );

        $expectedValues = array('test1', 'test2', 'test3');
        $index = 0;
        $objectList->expects($this->any())
            ->method('next')
            ->will($this->returnCallback(function() use($expectedValues, &$index) {
                if(isset($expectedValues[$index])){
                    $class =  new \stdClass();
                    $class->name = $expectedValues[$index];
                    $index++;
                    return $class;
                }
                return false;
            }));

        $container->expects($this->once())
            ->method('ObjectList')
            ->will($this->returnValue($objectList))
        ;

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertEquals($expectedValues, $sut->keys());
    }

    public function testKeys_noKeys_returnsEmptyArray(){

        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('ObjectList'),
            array(), '', false, false, false, false);

        $objectList = $this->getMock(
            'OpenCloud\Common\Collection',
            array('next'),
            array(), '', false, false, false, false
        );

        $expectedValues = array();
        $objectList->expects($this->Once())
            ->method('next')
            ->will($this->returnValue(false));

        $container->expects($this->once())
            ->method('ObjectList')
            ->will($this->returnValue($objectList))
        ;

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertEquals($expectedValues, $sut->keys());
    }

    public function testMtime_keyExists_ReturnCurrentTime(){
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $dataObject = $this->getMock(
            'OpenCloud\OpenStore\Resource\DataObject',
            array(),
            array(), '', false, false, false, false
        );

        $modDate = new \DateTime();

        $dataObject->last_modified = $modDate;

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->returnValue($dataObject));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertEquals($modDate, $sut->mtime('test'));
    }

    public function testMtime_keyDoesNotExist_ReturnFalse()
    {
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->throwException(new ObjFetchError()));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertFalse($sut->mtime('test'));
    }

    public function testDelete_keyExistsDeleteSuccess_ReturnTrue(){
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $dataObject = $this->getMock(
            'OpenCloud\OpenStore\Resource\DataObject',
            array('Delete'),
            array(), '', false, false, false, false
        );

        $dataObject->expects($this->once())
            ->method('Delete');

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->returnValue($dataObject));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));


        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertTrue($sut->delete('test'));

    }

    public function testDelete_keyExistsDeleteFailure_ReturnFalse(){
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $dataObject = $this->getMock(
            'OpenCloud\OpenStore\Resource\DataObject',
            array('Delete'),
            array(), '', false, false, false, false
        );

        $dataObject->expects($this->once())
            ->method('Delete')
            ->will($this->throwException(new DeleteError()));

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->returnValue($dataObject));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertFalse($sut->delete('test'));

    }

    public function testDelete_keyDoesNotExist_ReturnFalse(){
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->throwException(new ObjFetchError()));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertFalse($sut->delete('test'));
    }

    public function testCheckSum_keyExists_ReturnsString(){
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $dataObject = $this->getMock(
            'OpenCloud\OpenStore\Resource\DataObject',
            array('getETag'),
            array(), '', false, false, false, false
        );

        $testString = 'testString';

        $dataObject->expects($this->once())
            ->method('getETag')
            ->will($this->returnValue($testString));

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->returnValue($dataObject));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));


        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertSame($testString, $sut->checksum('test'));

    }

    public function testCheckSum_keyDoesNotExist_ReturnFalse(){
        $objectStore = $this->getMock(
            'OpenCloud\ObjectStore\Service',
            array('Container', 'Create'),
            array(), '', false, false, false, false);

        $container = $this->getMock(
            'OpenCloud\ObjectStore\Resource\Container',
            array('DataObject'),
            array(), '', false, false, false, false);

        $container->expects($this->once())
            ->method('DataObject')
            ->with($this->equalTo('test'))
            ->will($this->throwException(new ObjFetchError()));

        $objectStore->expects($this->once())
            ->method('Container')
            ->will($this->returnValue($container));

        $sut = $this->buildOpenCloudClass($objectStore, 'testContainer', false, true);

        $this->assertFalse($sut->checksum('test'));

    }

} 
