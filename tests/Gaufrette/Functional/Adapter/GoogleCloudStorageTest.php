<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\GoogleCloudStorage;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Filesystem;
use Google\Cloud\Storage\Acl;
use Google\Cloud\Storage\StorageClient;

/**
 * Functional tests for the GoogleCloudStorage adapter.
 * Edit the phpunit.xml.dist Google Cloud Storage adapter section for configuration
 *
 * @author  Lech Buszczynski <lecho@phatcat.eu>
 */
class GoogleCloudStorageTest extends FunctionalTestCase
{
    private $string    = 'Yeah mate. No worries, I uploaded just fine. Meow!';
    private $directory = 'tests';
    private $bucketName;
    private $sdkOptions;
    private $bucketOptions;

    public function setUp()
    {
        $gcsProjectId   = getenv('GCS_PROJECT_ID');
        $gcsBucketName  = getenv('GCS_BUCKET_NAME');
        $gcsJsonKeyFile = getenv('GCS_JSON_KEY_FILE');

        if (empty($gcsProjectId) || empty($gcsBucketName) || empty($gcsJsonKeyFile)) {
            $this->markTestSkipped('Either GCS_PROJECT_ID, GCS_BUCKET_NAME and/or GCS_JSON_KEY_FILE env vars are missing.');
        }

        $this->directory = uniqid($this->directory);
        $this->bucketName = $gcsBucketName;
        $this->sdkOptions = array(
            'projectId' => $gcsProjectId,
        );

        if ($this->isJsonString($gcsJsonKeyFile)) {
            $this->sdkOptions['keyFile'] = json_decode($gcsJsonKeyFile, true);
        } else {
            if (!is_readable($gcsJsonKeyFile)) {
                $this->markTestSkipped(sprintf('Cannot read JSON key file from "%s".', $gcsJsonKeyFile));
            }

            $this->sdkOptions['keyFilePath'] = $gcsJsonKeyFile;
        }

        $this->bucketOptions = array(
            'directory' => $this->directory,
            'acl'       => array(
                'allUsers' => Acl::ROLE_READER,
            ),
        );

        $storage = new StorageClient($this->sdkOptions);

        $adapter = new GoogleCloudStorage($storage, $this->bucketName, $this->bucketOptions);

        $this->filesystem = new Filesystem($adapter);
    }

    public function tearDown()
    {
        // make an other filesystem w/o custom root directory
        // (ie the root directory will be the bucket root directory)
        // to remove the uniqid'ed directory created by this test
        $storage = new StorageClient($this->sdkOptions);

        $adapter = new GoogleCloudStorage($storage, $this->bucketName, array_merge(
            $this->bucketOptions,
            array(
                'directory' => '',
            )
        ));

        $this->filesystem = new Filesystem($adapter);

        array_map(function ($key) {
            $this->filesystem->delete($key);
        }, $this->filesystem->keys());
    }

    /**
     * @test
     * @group functional
     * @group gcs
     *
     * @expectedException \Gaufrette\Exception\StorageFailure
     */
    public function shouldFailIfBucketIsNotAccessible()
    {
        $storage = new StorageClient($this->sdkOptions);

        new GoogleCloudStorage($storage, 'unexisting', $this->bucketOptions);
    }

    /**
     * @test
     * @group functional
     * @group gcs
     */
    public function shouldWriteAndReadFileMetadata()
    {
        /** @var \Gaufrette\Adapter\GoogleCloudStorage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $file   = 'PhatCat/Cat.txt';

        $this->filesystem->write($file, $this->string, true);
        $adapter->setMetadata($file, array('OhMy' => 'I am a cat file!'));
        $info = $adapter->getMetadata($file);

        $this->assertEquals($info['OhMy'], 'I am a cat file!');
    }

    /**
     * @test
     * @group functional
     * @group gcs
     */
    public function shouldTransfertMetadataWhenRenamingAFile()
    {
        /** @var \Gaufrette\Adapter\GoogleCloudStorage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $file   = 'Cat.txt';

        $this->filesystem->write($file, $this->string, true);
        $adapter->setMetadata($file, array('OhMy' => 'I am a cat file!'));
        $adapter->rename('Cat.txt', 'Kitten.txt');

        $this->assertEquals($adapter->getMetadata('Kitten.txt'), array('OhMy' => 'I am a cat file!'));
    }

    /**
     * @test
     * @group functional
     * @group gcs
     */
    public function shouldWriteAndReadPublicFile()
    {
        /** @var \Gaufrette\Adapter\GoogleCloudStorage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $file   = 'Cat.txt';
        $this->filesystem->write($file, $this->string, true);

        $publicLink = sprintf('https://storage.googleapis.com/%s/%s/Cat.txt', $adapter->getBucket()->name(), $this->directory);

        $headers = @get_headers($publicLink);
        $this->assertEquals($headers[0], 'HTTP/1.0 200 OK');
    }

    /**
     * @test
     * @group functional
     * @group gcs
     */
    public function shouldListKeys()
    {
        // empty bucket, no keys
        $this->assertEquals([], $this->filesystem->listKeys());

        // one item, one key
        $this->filesystem->write('file.txt', 'content');
        $this->assertEquals(['file.txt'], $this->filesystem->listKeys());

        // list only keys with the given prefix
        $this->filesystem->write('prefix/file.txt', 'content');
        $this->assertEquals(['prefix/file.txt'], $this->filesystem->listKeys('prefix'));
    }

    private function isJsonString($content) {
        json_decode($content);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
