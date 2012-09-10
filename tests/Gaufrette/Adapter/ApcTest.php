<?php

namespace Gaufrette\Adapter;

class ApcTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers Gaufrette\Adapter\Apc
     */
    public function shouldReadFromApc()
    {
        $adapter = $this->getMockBuilder('Gaufrette\Adapter\Apc')
            ->disableOriginalConstructor()
            ->setMethods(array('exists', 'apcFetch'))
            ->getMock();
        $adapter->expects($this->once())
            ->method('exists')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true));
        $adapter->expects($this->once())
            ->method('apcFetch')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue('Some content'));

        $this->assertSame('Some content', $adapter->read('foo'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Apc
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not read the 'foo' file.
     */
    public function shouldFailWhenCannotReadFromApc()
    {
        $adapter = $this->getMockBuilder('Gaufrette\Adapter\Apc')
            ->disableOriginalConstructor()
            ->setMethods(array('exists', 'apcFetch'))
            ->getMock();
        $adapter->expects($this->once())
            ->method('exists')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true));
        $adapter->expects($this->once())
            ->method('apcFetch')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(false));

        $adapter->read('foo');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Apc
     */
    public function shouldWriteToApc()
    {
        $adapter = $this->getMockBuilder('Gaufrette\Adapter\Apc')
            ->disableOriginalConstructor()
            ->setMethods(array('exists', 'apcStore'))
            ->getMock();
        $adapter->expects($this->once())
            ->method('apcStore')
            ->with($this->equalTo('foo'), $this->equalTo('Some content'))
            ->will($this->returnValue(true));

        $this->assertEquals(12, $adapter->write('foo', 'Some content'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Apc
     * @expectedException RuntimeException
     * @expectedExceptionMessage Could not write the 'foo' file.
     */
    public function shouldFailWhenCannotWriteToApc()
    {
        $adapter = $this->getMockBuilder('Gaufrette\Adapter\Apc')
            ->disableOriginalConstructor()
            ->setMethods(array('exists', 'apcStore'))
            ->getMock();
        $adapter->expects($this->once())
            ->method('apcStore')
            ->with($this->equalTo('foo'), $this->equalTo('Some content'))
            ->will($this->returnValue(false));

        $adapter->write('foo', 'Some content');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Apc
     */
    public function shouldCheckIfKeyExistsInApc()
    {
        $adapter = $this->getMockBuilder('Gaufrette\Adapter\Apc')
            ->disableOriginalConstructor()
            ->setMethods(array('apcExists'))
            ->getMock();
        $adapter->expects($this->at(0))
            ->method('apcExists')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true));
        $adapter->expects($this->at(1))
            ->method('apcExists')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(false));

        $this->assertTrue($adapter->exists('foo'));
        $this->assertFalse($adapter->exists('foo'));
    }

    /**
     * @test
     */
    public function shouldGetKeysFromApc()
    {
        $iterator = new \ArrayIterator(array('foo' => 'foovalue', 'bar' => 'barvalue'));

        if (!defined('APC_ITER_NONE')) {
            define('APC_ITER_NONE', 0);
        }

        $adapter = $this->getMockBuilder('Gaufrette\Adapter\Apc')
            ->disableOriginalConstructor()
            ->setMethods(array('getCachedKeysIterator'))
            ->getMock();
        $adapter->expects($this->once())
            ->method('getCachedKeysIterator')
            ->will($this->returnValue($iterator));

        $this->assertSame(array('bar', 'foo'), $adapter->keys());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function shouldFailWhenCannotFetchKeysFromApc()
    {
        if (!defined('APC_ITER_NONE')) {
            define('APC_ITER_NONE', 0);
        }

        $adapter = $this->getMockBuilder('Gaufrette\Adapter\Apc')
            ->disableOriginalConstructor()
            ->setMethods(array('getCachedKeysIterator'))
            ->getMock();
        $adapter->expects($this->once())
            ->method('getCachedKeysIterator')
            ->will($this->returnValue(null));

        $adapter->keys();
    }
}
