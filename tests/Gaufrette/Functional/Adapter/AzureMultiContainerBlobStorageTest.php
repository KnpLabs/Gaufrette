<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\AzureBlobStorage;
use Gaufrette\Adapter\AzureBlobStorage\BlobProxyFactory;
use Gaufrette\Filesystem;

/**
 * Class AzureMultiContainerBlobStorageTest
 * @group AzureBlobStorage
 * @group AzureMultiContainerBlobStorage
 */
class AzureMultiContainerBlobStorageTest extends FunctionalTestCase
{
    public function setUp()
    {
        $key = getenv('AZURE_KEY');
        $secret = getenv('AZURE_SECRET');
        if (empty($key) || empty($secret)) {
            $this->markTestSkipped();
        }

        $connection = sprintf('BlobEndpoint=http://%1$s.blob.core.windows.net/;AccountName=%1$s;AccountKey=%2$s', $key, $secret);

        $this->filesystem = new Filesystem(new AzureBlobStorage(new BlobProxyFactory($connection)));
    }

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
     * @test
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
        $this->filesystem->write('container8-1/foo', 'Some content');
        $this->filesystem->write('container8-2/bar', 'Some content');
        $this->filesystem->write('container8-3/baz', 'Some content');

        $actualKeys = $this->filesystem->keys();
        foreach (['container8-1/foo', 'container8-2/bar', 'container8-3/baz'] as $key) {
            $this->assertContains($key, $actualKeys);
        }

        $this->filesystem->delete('container8-1/foo');
        $this->filesystem->delete('container8-2/bar');
        $this->filesystem->delete('container8-3/baz');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWorkWithHiddenFiles()
    {
        $this->filesystem->write('container9/.foo', 'hidden');
        $this->assertTrue($this->filesystem->has('container9/.foo'));
        $this->assertContains('container9/.foo', $this->filesystem->keys());
        $this->filesystem->delete('container9/.foo');
        $this->assertFalse($this->filesystem->has('container9/.foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldKeepFileObjectInRegister()
    {
        $FileObjectA = $this->filesystem->createFile('container10/somefile');
        $FileObjectB = $this->filesystem->createFile('container10/somefile');

        $this->assertTrue($FileObjectA === $FileObjectB);
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWrtieToSameFile()
    {
        $FileObjectA = $this->filesystem->createFile('container11/somefile');
        $FileObjectA->setContent('ABC');

        $FileObjectB = $this->filesystem->createFile('container11/somefile');
        $FileObjectB->setContent('DEF');

        $this->assertEquals('DEF', $FileObjectB->getContent());

        $this->filesystem->delete('container11/somefile');
    }
}
