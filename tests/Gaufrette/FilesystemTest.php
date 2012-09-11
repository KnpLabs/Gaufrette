<?php

namespace Gaufrette;

use Gaufrette\Adapter\InMemory;

class FilesystemTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldGetAFileInstanceConfiguredForTheKeyAndFilesystem()
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

    /**
     * @test
     */
    public function shouldFailWhenTryGetTheFileWhichDoesNotExist()
    {
        $this->setExpectedException('InvalidArgumentException');
        $adapter = new InMemory();

        $fs = new Filesystem($adapter);
        $fs->get('myFile');
    }

    /**
     * @test
     */
    public function shouldFailWhenTryReadTheFileWhichDoesNotExist()
    {
        $this->setExpectedException('InvalidArgumentException');
        $adapter = new InMemory();

        $fs = new Filesystem($adapter);
        $fs->read('myFile');
    }

    /**
     * @test
     */
    public function shouldFailWhenTryWriteFileWhichExistsAndCannotBeOverwrite()
    {
        $this->setExpectedException('InvalidArgumentException');

        $adapter = new InMemory(array(
            'myFile' => array()
        ));

        $fs = new Filesystem($adapter);
        $fs->write('myFile', 'some text');
    }
}
