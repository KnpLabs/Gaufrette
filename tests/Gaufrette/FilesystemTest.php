<?php

namespace Gaufrette;

use Gaufrette\Adapter\InMemory;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    public function testGetReturnsAFileInstanceConfiguredForTheKeyAndFilesystem()
    {
        $adapter = new InMemory(array(
            'myFile' => array()
        ));

        $fs = new Filesystem($adapter);

        $file = $fs->get('myFile');

        $this->assertInstanceOf('Gaufrette\File', $file);
        $this->assertEquals('myFile', $file->getKey());
        $this->assertEquals($fs, $file->getFilesystem());
    }

    public function testGetThrowsAnExceptionIfTheFileDoesNotExistAndTheCreateParameterIsSetToFalse()
    {
        $adapter = new InMemory();

        $fs = new Filesystem($adapter);

        $this->setExpectedException('InvalidArgumentException');

        $fs->get('myFile');
    }

    public function testReadThrowsAnExceptionIfTheKeyDoesNotMatchAnyFile()
    {
        $adapter = new InMemory();

        $fs = new Filesystem($adapter);

        $this->setExpectedException('InvalidArgumentException');

        $fs->read('myFile');
    }

    public function testWriteThrowsAnExceptionIfTheFileAlreadyExistsAndIsNotAllowedToOverwrite()
    {
        $adapter = new InMemory(array(
            'myFile' => array()
        ));

        $fs = new Filesystem($adapter);

        $this->setExpectedException('InvalidArgumentException');

        $fs->write('myFile', 'some text');
    }
}
