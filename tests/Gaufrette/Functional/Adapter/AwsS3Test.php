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
            $this->markTestSkipped();
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
    }

    public function tearDown()
    {
        if ($this->client === null || !$this->client->doesBucketExist($this->bucket)) {
            return;
        }

        $result = $this->client->listObjects(['Bucket' => $this->bucket]);
        $staleObjects = $result->get('Contents');

        foreach ($staleObjects as $staleObject) {
            $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $staleObject['Key']]);
        }

        $this->client->deleteBucket(['Bucket' => $this->bucket]);
    }

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
        $expected   = sprintf('https://%s.s3-eu-west-1.amazonaws.com/foo', $this->bucket);

        if (self::$SDK_VERSION === 3) {
            $expected = sprintf('https://s3-eu-west-1.amazonaws.com/%s/foo', $this->bucket);
        }

        $this->assertEquals(
            $expected,
            $filesystem->getAdapter()->getUrl('foo')
        );
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
        $expected   = sprintf('https://%s.s3-eu-west-1.amazonaws.com/bar/foo', $this->bucket);

        if (self::$SDK_VERSION === 3) {
            $expected = sprintf('https://s3-eu-west-1.amazonaws.com/%s/bar/foo', $this->bucket);
        }

        $this->assertEquals(
            $expected,
            $filesystem->getAdapter()->getUrl('foo')
        );
    }

    public function shouldListKeysWithoutDirectory()
    {
        $filesystem = $this->getFilesystem();
        $filesystem->write('test.txt', 'some content');
        $keys = $filesystem->listKeys();
        $this->assertEquals('test.txt', $keys['key']);
    }
}
