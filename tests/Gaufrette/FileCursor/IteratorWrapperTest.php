<?php

namespace Gaufrette\FileCursor;

use Gaufrette\Filesystem;

class IteratorWrapperTest extends \PHPUnit_Framework_TestCase
{
    public function testImplementsIterator()
    {
        $this->assertInstanceOf('Iterator', $this->newInstance());
    }

    public function newInstance(\Iterator $iterator = null, Filesystem $filesystem = null)
    {
        if (null === $iterator) {
            $iterator = $this->getIteratorMock();
        }

        if (null === $filesystem) {
            $filesystem = $this->getFilesystemMock();
        }

        return $this->getMockForAbstractClass('Gaufrette\FileCursor\IteratorWrapper', array($iterator, $filesystem));
    }

    public function getIteratorMock()
    {
        return $this->getMock('Iterator');
    }

    public function getFilesystemMock()
    {
        return $this->getMock('Gaufrette\Filesystem', array(), array(), '', false);
    }
}
