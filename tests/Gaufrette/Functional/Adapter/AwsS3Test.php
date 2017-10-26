<?php

namespace Gaufrette\Functional\Adapter;

use Aws\S3\S3Client;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Filesystem;

class AwsS3Test extends FunctionalTestCase
{
    /** @var int */
    static private $SDK_VERSION;

    /** @var string */
    private $bucket;

    /** @var S3Client */
    private $client;

    public function setUp()
    {
        $key = getenv('AWS_KEY');
        $secret = getenv('AWS_SECRET');

        if (empty($key) || empty($secret)) {
            $this->markTestSkipped('Either AWS_KEY and/or AWS_SECRET env variables are not defined.');
        }

        if (self::$SDK_VERSION === null) {
            self::$SDK_VERSION = method_exists(S3Client::class, 'getArguments') ? 3 : 2;
        }

        $this->bucket = uniqid(getenv('AWS_BUCKET'));

        if (self::$SDK_VERSION === 3) {
            // New way of instantiating S3Client for aws-sdk-php v3
            $this->client = new S3Client([
                'region' => 'eu-west-1',
                'version' => 'latest',
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ],
            ]);
        } else {
            $this->client = S3Client::factory([
                'region' => 'eu-west-1',
                'version' => '2006-03-01',
                'key' => $key,
                'secret' => $secret,
            ]);
        }

        $this->createFilesystem(['create' => true]);
    }

    public function tearDown()
    {
        if ($this->client === null || !$this->client->doesBucketExist($this->bucket)) {
            return;
        }

        $result = $this->client->listObjects(['Bucket' => $this->bucket]);

        if (!$result->hasKey('Contents')) {
            $this->client->deleteBucket(['Bucket' => $this->bucket]);
            return;
        }

        foreach ($result->get('Contents') as $staleObject) {
            $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $staleObject['Key']]);
        }

        $this->client->deleteBucket(['Bucket' => $this->bucket]);
    }

    private function createFilesystem(array $adapterOptions = [])
    {
        $this->filesystem = new Filesystem(new AwsS3($this->client, $this->bucket, $adapterOptions));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testThrowsExceptionIfBucketMissingAndNotCreating()
    {
        $this->createFilesystem();
        $this->filesystem->read('foo');
    }

    public function testWritesObjects()
    {
        $this->assertEquals(7, $this->filesystem->write('foo', 'testing'));
    }

    public function testChecksForObjectExistence()
    {
        $this->filesystem->write('foo', '');
        $this->assertTrue($this->filesystem->has('foo'));
    }

    public function testGetsObjectUrls()
    {
        $this->assertNotEmpty($this->filesystem->getAdapter()->getUrl('foo'));
    }

    public function testChecksForObjectExistenceWithDirectory()
    {
        $this->createFilesystem(['directory' => 'bar', 'create' => true]);
        $this->filesystem->write('foo', '');

        $this->assertTrue($this->filesystem->has('foo'));
    }

    public function testGetsObjectUrlsWithDirectory()
    {
        $this->createFilesystem(['directory' => 'bar']);
        $this->assertNotEmpty($this->filesystem->getAdapter()->getUrl('foo'));
    }

    public function testListKeysWithoutDirectory()
    {
        $this->assertEquals([], $this->filesystem->listKeys());
        $this->filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $this->filesystem->listKeys());
    }

    public function testListKeysWithDirectory()
    {
        $this->createFilesystem(['create' => true, 'directory' => 'root/']);
        $this->filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $this->filesystem->listKeys());
        $this->assertTrue($this->filesystem->has('test.txt'));
    }

    public function testKeysWithoutDirectory()
    {
        $this->filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $this->filesystem->keys());
    }

    public function testKeysWithDirectory()
    {
        $this->createFilesystem(['create' => true, 'directory' => 'root/']);
        $this->filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $this->filesystem->keys());
    }

    public function testUploadWithGivenContentType()
    {
        /** @var AwsS3 $adapter */
        $adapter = $this->filesystem->getAdapter();

        $adapter->setMetadata('foo', ['ContentType' => 'text/html']);
        $this->filesystem->write('foo', '<html></html>');

        $this->assertEquals('text/html', $this->filesystem->mimeType('foo'));
    }
}
