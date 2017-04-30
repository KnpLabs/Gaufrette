<?php

namespace Gaufrette\Functional\Adapter;

/**
 * Class AzureMultiContainerBlobStorageTest
 * @group AzureBlobStorage
 * @group AzureMultiContainerBlobStorage
 */
class AzureMultiContainerBlobStorageTest extends FunctionalTestCase
{
    /**
     * @test
     * @group functional
     */
    public function shouldWriteAndRead()
    {
        $this->assertEquals(12, $this->filesystem->write('container1/foo', 'Some content'));
        $this->assertEquals(13, $this->filesystem->write('test/subdir/foo', 'Some content1', true));

        $this->assertEquals('Some content', $this->filesystem->read('container1/foo'));
        $this->assertEquals('Some content1', $this->filesystem->read('test/subdir/foo'));
        $this->filesystem->delete('container1/foo');
        $this->filesystem->delete('test/subdir/foo');
    }

    /**
     * @teste
     * @group functional
     */
    public function shouldUpdateFileContent()
    {
        $this->filesystem->write('container2/foo', 'Some content');
        $this->filesystem->write('container2/foo', 'Some content updated', true);

        $this->assertEquals('Some content updated', $this->filesystem->read('container2/foo'));
        $this->filesystem->delete('container2/foo');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldCheckIfFileExists()
    {
        $this->assertFalse($this->filesystem->has('container3/foo'));

        $this->filesystem->write('container3/foo', 'Some content');

        $this->assertTrue($this->filesystem->has('container3/foo'));
        $this->assertFalse($this->filesystem->has('test/somefile'));
        $this->assertFalse($this->filesystem->has('test/somefile'));

        $this->filesystem->delete('container3/foo');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetMtime()
    {
        $this->filesystem->write('container4/foo', 'Some content');

        $this->assertGreaterThan(0, $this->filesystem->mtime('container4/foo'));

        $this->filesystem->delete('container4/foo');
    }

    /**
     * @test
     * @group functional
     * @expectedException \RuntimeException
     * @expectedMessage Could not get mtime for the "foo" key
     */
    public function shouldFailWhenTryMtimeForKeyWhichDoesNotExist()
    {
        $this->assertFalse($this->filesystem->mtime('container5/foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldRenameFile()
    {
        $this->filesystem->write('container6/foo', 'Some content');
        $this->filesystem->rename('container6/foo', 'container6-new/boo');

        $this->assertFalse($this->filesystem->has('container6/foo'));
        $this->assertEquals('Some content', $this->filesystem->read('container6-new/boo'));
        $this->filesystem->delete('container6-new/boo');

        $this->filesystem->write('container6/foo', 'Some content');
        $this->filesystem->rename('container6/foo', 'somedir/sub/boo');

        $this->assertFalse($this->filesystem->has('somedir/sub/foo'));
        $this->assertEquals('Some content', $this->filesystem->read('somedir/sub/boo'));
        $this->filesystem->delete('somedir/sub/boo');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldDeleteFile()
    {
        $this->filesystem->write('container7/foo', 'Some content');

        $this->assertTrue($this->filesystem->has('container7/foo'));

        $this->filesystem->delete('container7/foo');

        $this->assertFalse($this->filesystem->has('container7/foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldFetchKeys()
    {
        $this->assertEquals(array(), $this->filesystem->keys());

        $this->filesystem->write('container/foo', 'Some content');
        $this->filesystem->write('container/bar', 'Some content');
        $this->filesystem->write('container/baz', 'Some content');

        $actualKeys = $this->filesystem->keys();

        $this->assertEquals(3, count($actualKeys));
        foreach (array('foo', 'bar', 'baz') as $key) {
            $this->assertContains($key, $actualKeys);
        }

        $this->filesystem->delete('container/foo');
        $this->filesystem->delete('container/bar');
        $this->filesystem->delete('container/baz');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWorkWithHiddenFiles()
    {
        $this->filesystem->write('container/.foo', 'hidden');
        $this->assertTrue($this->filesystem->has('container/.foo'));
        $this->assertContains('container/.foo', $this->filesystem->keys());
        $this->filesystem->delete('container/.foo');
        $this->assertFalse($this->filesystem->has('container/.foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldKeepFileObjectInRegister()
    {
        $FileObjectA = $this->filesystem->createFile('container/somefile');
        $FileObjectB = $this->filesystem->createFile('container/somefile');

        $this->assertTrue($FileObjectA === $FileObjectB);
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWrtieToSameFile()
    {
        $FileObjectA = $this->filesystem->createFile('container/somefile');
        $FileObjectA->setContent('ABC');

        $FileObjectB = $this->filesystem->createFile('container/somefile');
        $FileObjectB->setContent('DEF');

        $this->assertEquals('DEF', $FileObjectB->getContent());

        $this->filesystem->delete('container/somefile');
    }
}
