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
    private $adapter;

    private $containers = [];

    protected function setUp()
    {
        $this->markTestSkipped(__CLASS__ . ' is flaky.');

        $account = getenv('AZURE_ACCOUNT');
        $key = getenv('AZURE_KEY');
        if (empty($account) || empty($key)) {
            $this->markTestSkipped('Either AZURE_ACCOUNT and/or AZURE_KEY env variables are not defined.');
        }

        $connection = sprintf('BlobEndpoint=http://%1$s.blob.core.windows.net/;AccountName=%1$s;AccountKey=%2$s', $account, $key);

        $this->adapter = new AzureBlobStorage(new BlobProxyFactory($connection));
        $this->filesystem = new Filesystem($this->adapter);
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWriteAndRead()
    {
        $path1 = $this->createUniqueContainerName('container') . '/foo';
        $path2 = $this->createUniqueContainerName('test') . '/subdir/foo';

        $this->assertEquals(12, $this->filesystem->write($path1, 'Some content'));
        $this->assertEquals(13, $this->filesystem->write($path2, 'Some content1', true));

        $this->assertEquals('Some content', $this->filesystem->read($path1));
        $this->assertEquals('Some content1', $this->filesystem->read($path2));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldUpdateFileContent()
    {
        $path = $this->createUniqueContainerName('container') . '/foo';

        $this->filesystem->write($path, 'Some content');
        $this->filesystem->write($path, 'Some content updated', true);

        $this->assertEquals('Some content updated', $this->filesystem->read($path));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldCheckIfFileExists()
    {
        $path1 = $this->createUniqueContainerName('container') . '/foo';
        $path2 = $this->createUniqueContainerName('test') . '/somefile';

        $this->assertFalse($this->filesystem->has($path1));

        $this->filesystem->write($path1, 'Some content');

        $this->assertTrue($this->filesystem->has($path1));
        // @TODO: why is it done two times?
        $this->assertFalse($this->filesystem->has($path2));
        $this->assertFalse($this->filesystem->has($path2));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetMtime()
    {
        $path = $this->createUniqueContainerName('container') . '/foo';

        $this->filesystem->write($path, 'Some content');

        $this->assertGreaterThan(0, $this->filesystem->mtime($path));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetSize()
    {
        $path = $this->createUniqueContainerName('container') . '/foo';

        $contentSize = $this->filesystem->write($path, 'Some content');

        $this->assertEquals($contentSize, $this->filesystem->size($path));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetMd5Hash()
    {
        $path = $this->createUniqueContainerName('container') . '/foo';

        $content = 'Some content';
        $this->filesystem->write($path, $content);

        $this->assertEquals(\md5($content), $this->filesystem->checksum($path));
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
        $somedir = $this->createUniqueContainerName('somedir');
        $path1 = $this->createUniqueContainerName('container') . '/foo';
        $path2 = $this->createUniqueContainerName('container-new') . '/boo';
        $path3 = $somedir . '/sub/boo';

        $this->filesystem->write($path1, 'Some content');
        $this->filesystem->rename($path1, $path2);

        $this->assertFalse($this->filesystem->has($path1));
        $this->assertEquals('Some content', $this->filesystem->read($path2));

        $this->filesystem->write($path1, 'Some content');
        $this->filesystem->rename($path1, $path3);

        $this->assertFalse($this->filesystem->has($somedir . '/sub/foo'));
        $this->assertEquals('Some content', $this->filesystem->read($path3));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldDeleteFile()
    {
        $path = $this->createUniqueContainerName('container') . '/foo';

        $this->filesystem->write($path, 'Some content');

        $this->assertTrue($this->filesystem->has($path));

        $this->filesystem->delete($path);

        $this->assertFalse($this->filesystem->has($path));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldFetchKeys()
    {
        $path1 = $this->createUniqueContainerName('container-1') . '/foo';
        $path2 = $this->createUniqueContainerName('container-2') . '/bar';
        $path3 = $this->createUniqueContainerName('container-3') . '/baz';

        $this->filesystem->write($path1, 'Some content');
        $this->filesystem->write($path2, 'Some content');
        $this->filesystem->write($path3, 'Some content');

        $actualKeys = $this->filesystem->keys();
        foreach ([$path1, $path2, $path3] as $key) {
            $this->assertContains($key, $actualKeys);
        }
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWorkWithHiddenFiles()
    {
        $path = $this->createUniqueContainerName('container') . '/.foo';

        $this->filesystem->write($path, 'hidden');
        $this->assertTrue($this->filesystem->has($path));
        $this->assertContains($path, $this->filesystem->keys());
        $this->filesystem->delete($path);
        $this->assertFalse($this->filesystem->has($path));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldKeepFileObjectInRegister()
    {
        $path = $this->createUniqueContainerName('container') . '/somefile';

        $FileObjectA = $this->filesystem->createFile($path);
        $FileObjectB = $this->filesystem->createFile($path);

        $this->assertSame($FileObjectB, $FileObjectA);
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWriteToSameFile()
    {
        $path = $this->createUniqueContainerName('container') . '/somefile';

        $FileObjectA = $this->filesystem->createFile($path);
        $FileObjectA->setContent('ABC');

        $FileObjectB = $this->filesystem->createFile($path);
        $FileObjectB->setContent('DEF');

        $this->assertEquals('DEF', $FileObjectA->getContent());
    }

    private function createUniqueContainerName($prefix)
    {
        $this->containers[] = $container = uniqid($prefix);

        return $container;
    }

    protected function tearDown()
    {
        foreach ($this->containers as $container) {
            $this->adapter->deleteContainer($container);
        }
    }
}
