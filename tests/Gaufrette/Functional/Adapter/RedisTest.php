<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Redis;
use Predis\Client;
use Gaufrette\File;

/**
 * RedisTestCase
 *
 * @group redis-adapter
 */
abstract class RedisTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Client
     */
    protected $client;
//
//    public function setUp()
//    {
//        //@todo mock client object
//        $this->client = new Client(Array('host' => '127.0.0.1', 'port' => '6379', 'database' => 'test'));
//        $this->filesystem = new Filesystem(new Redis($this->client, 'collection'));
//    }
//
//    public function testWriteAndRead()
//    {
//        $image = new File('testRead.png', $this->filesystem);
//        $imageContent = file_get_contents("http://redis.io/images/redis.png");
//        $image->setContent($imageContent);
//
//        $file = new File('file2.txt', $this->filesystem);
//        $txtContent = 'some content';
//        $file->setContent($txtContent);
//
//        $this->assertEquals($this->filesystem->get('testRead.png')->getContent(), $imageContent);
//        $this->assertEquals($this->filesystem->get('file2.txt')->getContent(), $txtContent);
//    }
//
//    public function testDelete()
//    {
//        $file = new File('file.jpg', $this->filesystem);
//        $file->setContent('some content');
//        $this->filesystem->delete('file.jpg');
//        $this->assertFalse($this->filesystem->has('file.jpg'));
//    }
//
//    public function testRename()
//    {
//        $file = new File('original.jpg', $this->filesystem);
//        $file->setContent('some content');
//        $this->filesystem->rename('original.jpg', 'renamed.jpg');
//        $this->assertFalse($this->filesystem->has('file.jpg'));
//        $this->assertTrue($this->filesystem->has('renamed.jpg'));
//    }
//
//    /**
//     * @expectedException \Gaufrette\Exception\FileNotFound
//     */
//    public function testThrowExceptionOnUnrecognizedKey()
//    {
//        $this->filesystem->rename('unknown.jpg', 'target.jpg');
//    }
//
//    public function tearDown()
//    {
//        $this->client->executeCommand(
//            $this->client->createCommand('FLUSHDB')
//        );
//    }
}

