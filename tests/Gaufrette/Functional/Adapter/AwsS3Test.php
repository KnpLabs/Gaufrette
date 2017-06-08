<?php

namespace Gaufrette\Functional\Adapter;

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Filesystem;

/**
 * @todo move to phpspec
 */
class AwsS3Test extends \PHPUnit_Framework_TestCase
{
    use AwsS3SetUpTearDownTrait;

    /** @var int */
    static private $SDK_VERSION;

    private function getFilesystem(array $adapterOptions = [])
    {
        return new Filesystem(new AwsS3($this->client, $this->bucket, $adapterOptions));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowsExceptionIfBucketMissingAndNotCreating()
    {
        $filesystem = $this->getFilesystem();
        $filesystem->read('foo');
    }

    public function testWritesObjects()
    {
        $filesystem = $this->getFilesystem(['create' => true]);
        $this->assertEquals(7, $filesystem->write('foo', 'testing'));
    }

    public function testChecksForObjectExistence()
    {
        $filesystem = $this->getFilesystem(['create' => true]);
        $filesystem->write('foo', '');
        $this->assertTrue($filesystem->has('foo'));
    }

    public function testGetsObjectUrls()
    {
        $filesystem = $this->getFilesystem(['create' => true]);
        $this->assertNotEmpty($filesystem->getAdapter()->getUrl('foo'));
    }

    public function testChecksForObjectExistenceWithDirectory()
    {
        $filesystem = $this->getFilesystem(['directory' => 'bar', 'create' => true]);
        $filesystem->write('foo', '');

        $this->assertTrue($filesystem->has('foo'));
    }

    public function testGetsObjectUrlsWithDirectory()
    {
        $filesystem = $this->getFilesystem(['directory' => 'bar']);
        $this->assertNotEmpty($filesystem->getAdapter()->getUrl('foo'));
    }

    public function testListKeysWithoutDirectory()
    {
        $filesystem = $this->getFilesystem(['create' => true]);
        $filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $filesystem->listKeys());
    }

    public function testListKeysWithDirectory()
    {
        $filesystem = $this->getFilesystem(['create' => true, 'directory' => 'root/']);
        $filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $filesystem->listKeys());
        $this->assertTrue($filesystem->has('test.txt'));
    }

    public function testKeysWithoutDirectory()
    {
        $filesystem = $this->getFilesystem(['create' => true]);
        $filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $filesystem->keys());
    }

    public function testKeysWithDirectory()
    {
        $filesystem = $this->getFilesystem(['create' => true, 'directory' => 'root/']);
        $filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $filesystem->keys());
    }

    public function testUploadWithGivenContentType()
    {
        $filesystem = $this->getFilesystem(['create' => true]);
        /** @var AwsS3 $adapter */
        $adapter = $filesystem->getAdapter();

        $adapter->setMetadata('foo', ['ContentType' => 'text/html']);
        $filesystem->write('foo', '<html></html>');

        $this->assertEquals('text/html', $filesystem->mimeType('foo'));
    }
}
