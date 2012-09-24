<?php

namespace Gaufrette\Adapter;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers Gaufrette\Adapter\Base
     */
    public function shouldCreateFile()
    {
        $filesystem = $this->getMockBuilder('Gaufrette\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();

        $adapter = $this->getMockForAbstractClass('Gaufrette\Adapter\Base');
        $this->assertInstanceOf('Gaufrette\File', $adapter->createFile('test', $filesystem));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Base
     */
    public function shouldCreateFileStream()
    {
        $filesystem = $this->getMockBuilder('Gaufrette\Filesystem')
            ->disableOriginalConstructor()
            ->getMock();

        $adapter = $this->getMockForAbstractClass('Gaufrette\Adapter\Base');
        $this->assertInstanceOf('Gaufrette\FileStream\InMemoryBuffer', $adapter->createFileStream('test', $filesystem));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Base
     */
    public function shouldNotSupportMetadata()
    {
        $adapter = $this->getMockForAbstractClass('Gaufrette\Adapter\Base');
        $this->assertFalse($adapter->supportsMetadata());
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\Base
     */
    public function shouldNotListDirecotry()
    {
        $adapter = $this->getMockForAbstractClass('Gaufrette\Adapter\Base');
        $this->assertFalse($adapter->listDirectory());
    }
}
