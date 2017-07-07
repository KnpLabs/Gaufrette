<?php

namespace Gaufrette\Functional\Adapter;

trait FileNotFoundTests
{
    /**
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function testAdapterShouldThrowAnExceptionIfTheFileReadDoesNotExist()
    {
        $this->filesystem->getAdapter()->read('does-not-exist');
    }

    /**
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function testAdapterShouldThrowAnExceptionIfTheFileRenamedDoesNotExist()
    {
        $this->filesystem->getAdapter()->rename('does-not-exist', 'foo');
    }

    /**
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function testAdapterShouldThrowAnExceptionIfTheFileDeletedDoesNotExist()
    {
        $this->filesystem->getAdapter()->delete('does-not-exist');
    }

    /**
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function testAdapterShouldThrowAnExceptionIfTheMtimeIsRetrievedForAnNonExistentFile()
    {
        $this->filesystem->getAdapter()->mtime('does-not-exist');
    }

    /**
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function testAdapterShouldThrowAnExceptionIfTheSizeIsRetrievedForAnNonExistentFile()
    {
        $this->filesystem->getAdapter()->size('does-not-exist');
    }
}
