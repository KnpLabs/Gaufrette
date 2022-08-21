<?php

namespace Gaufrette\Functional\Adapter;

use AsyncAws\SimpleS3\SimpleS3Client;
use Gaufrette\Adapter\AsyncAwsS3;
use Gaufrette\Adapter\AwsS3;
use Gaufrette\Filesystem;

class AsyncAwsS3Test extends FunctionalTestCase
{
    /** @var string */
    private $bucket;

    /** @var SimpleS3Client */
    private $client;

    protected function setUp(): void
    {
        if (!class_exists(SimpleS3Client::class)) {
            $this->markTestSkipped('You need to install async-aws/simple-s3 to run this test.');
        }
        $key = getenv('AWS_KEY');
        $secret = getenv('AWS_SECRET');

        if (empty($key) || empty($secret)) {
            $this->markTestSkipped('Either AWS_KEY and/or AWS_SECRET env variables are not defined.');
        }

        $this->bucket = uniqid(getenv('AWS_BUCKET'));
        $this->client = new SimpleS3Client([
            'region' => 'eu-west-1',
            'accessKeyId' => $key,
            'accessKeySecret' => $secret,
        ]);

        $this->createFilesystem(['create' => true]);
    }

    protected function tearDown(): void
    {
        if ($this->client === null) {
            return;
        }

        try {
            $files = $this->filesystem->listKeys();
            foreach ($files as $file) {
                $this->filesystem->delete($file);
            }
            $this->client->deleteBucket(['Bucket' => $this->bucket]);
        } catch (\Throwable $e) {
        }
    }

    private function createFilesystem(array $adapterOptions = [])
    {
        $this->filesystem = new Filesystem(new AsyncAwsS3($this->client, $this->bucket, $adapterOptions));
    }

    /**
     * @test
     */
    public function shouldThrowExceptionIfBucketMissingAndNotCreating(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->createFilesystem();
        $this->filesystem->read('foo');
    }

    /**
     * @test
     */
    public function shouldWriteObjects(): void
    {
        $this->assertEquals(7, $this->filesystem->write('foo', 'testing'));
    }

    /**
     * @test
     */
    public function shouldCheckForObjectExistence(): void
    {
        $this->filesystem->write('foo', '');
        $this->assertTrue($this->filesystem->has('foo'));
    }

    /**
     * @test
     */
    public function shouldCheckForObjectExistenceWithDirectory(): void
    {
        $this->createFilesystem(['directory' => 'bar', 'create' => true]);
        $this->filesystem->write('foo', '');

        $this->assertTrue($this->filesystem->has('foo'));
    }

    /**
     * @test
     */
    public function shouldListKeysWithoutDirectory(): void
    {
        $this->assertEquals([], $this->filesystem->listKeys());
        $this->filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $this->filesystem->listKeys());
    }

    /**
     * @test
     */
    public function shouldListKeysWithDirectory(): void
    {
        $this->createFilesystem(['create' => true, 'directory' => 'root/']);
        $this->filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $this->filesystem->listKeys());
        $this->assertTrue($this->filesystem->has('test.txt'));
    }

    /**
     * @test
     */
    public function shouldGetKeysWithoutDirectory(): void
    {
        $this->filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $this->filesystem->keys());
    }

    /**
     * @test
     */
    public function shouldGetKeysWithDirectory(): void
    {
        $this->createFilesystem(['create' => true, 'directory' => 'root/']);
        $this->filesystem->write('test.txt', 'some content');
        $this->assertEquals(['test.txt'], $this->filesystem->keys());
    }

    /**
     * @test
     */
    public function shouldUploadWithGivenContentType(): void
    {
        /** @var AwsS3 $adapter */
        $adapter = $this->filesystem->getAdapter();

        $adapter->setMetadata('foo', ['ContentType' => 'text/html']);
        $this->filesystem->write('foo', '<html></html>');

        $this->assertEquals('text/html', $this->filesystem->mimeType('foo'));
    }
}
