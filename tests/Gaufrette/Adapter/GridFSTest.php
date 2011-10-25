<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Filesystem;

class GridFSTest extends \PHPUnit_Framework_TestCase
{
    protected $testHost = 'localhost:27017';
    protected $testDatabase = 'gaufrette_test';

    private $testFileKey = '/testkey/memo.txt';
    private $testFileContent = 'Lorem Ipsum...';
    private $testFileMetadata = array('foo' => 'bar');

    protected $gridfs;

    public function setUp()
    {
        if (!class_exists('\Mongo')) {
            $this->markTestSkipped('Mongo class not found.');
        }

        // Connect to MongoDB
        $connection = new \Mongo($this->testHost);

        if (!$connection->connected) {
            $this->markTestSkipped('Cannot connect to Mongo server.');
        }

        // Get MongoGridFS object
        $obj = $connection->selectDB($this->testDatabase)->getGridFS();

        if (!($obj instanceof \MongoGridFS)) {
            $this->markTestSkipped('Cannot fetch MongoGridFS object.');
        }

        // Create instance of the adapter
        $this->gridfs = new GridFS($obj);

        if (!is_object($this->gridfs)) {
            $this->markTestSkipped('Cannot create object from adapter.');
        }
    }

    public function testWriteReadDelete()
    {
        $this->assertGreaterThan(0, $this->gridfs->write($this->testFileKey, $this->testFileContent, $this->testFileMetadata));
        $this->assertTrue($this->gridfs->exists($this->testFileKey));

        $this->assertEquals($this->gridfs->read($this->testFileKey), $this->testFileContent);

        $this->assertTrue($this->gridfs->delete($this->testFileKey));
        $this->assertFalse($this->gridfs->exists($this->testFileKey));
    }

    public function testRename()
    {
        $newTestFileKey = '/newtestkey/updated_memo.txt';

        $this->assertGreaterThan(0, $this->gridfs->write($this->testFileKey, $this->testFileContent, $this->testFileMetadata));
        $this->assertTrue($this->gridfs->exists($this->testFileKey));

        $this->assertTrue($this->gridfs->rename($this->testFileKey, $newTestFileKey));
        $this->assertTrue($this->gridfs->exists($newTestFileKey));

        $this->assertEquals($this->gridfs->read($newTestFileKey), $this->testFileContent);

        $this->assertFalse($this->gridfs->exists($this->testFileKey));
        $this->assertTrue($this->gridfs->delete($newTestFileKey));
    }

    public function testQuery()
    {
        $filenames = array($this->testFileKey, $this->testFileKey . '.rtf', $this->testFileKey . '.pdf');

        // Query requires Filesystem object
        if (!class_exists('Gaufrette\Filesystem')) {
            $this->markTestSkipped('Cannot find Filesystem object. Test for query() -method skipped.');
        }

        foreach ($filenames as $file) {
            $this->assertGreaterThan(0, $this->gridfs->write($file, $this->testFileContent, $this->testFileMetadata));
        }

        $rs = $this->gridfs->query('/testkey/', new Filesystem($this->gridfs));
        $i = 0;

        foreach ($rs as $row) {
            $this->gridfs->delete($row->getKey());
            $i++;
        }

        $this->assertEquals($i, count($filenames));
    }
}
