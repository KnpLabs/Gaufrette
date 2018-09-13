<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\GridFS;
use Gaufrette\Filesystem;
use MongoDB\Client;

class GridFSTest extends FunctionalTestCase
{
    public function setUp()
    {
        $uri = getenv('MONGO_URI');
        $dbname = getenv('MONGO_DBNAME');

        if ($uri === false || $dbname === false) {
            $this->markTestSkipped('Either MONGO_URI or MONGO_DBNAME env variables are not defined.');
        }

        $client = new Client($uri);
        $db = $client->selectDatabase($dbname);
        $bucket = $db->selectGridFSBucket();
        $bucket->drop();

        $this->filesystem = new Filesystem(new GridFS($bucket));
    }

    /**
     * @test
     */
    public function shouldListKeys()
    {
        $this->filesystem->write('foo/foobar/bar.txt', 'data');
        $this->filesystem->write('foo/bar/buzz.txt', 'data');
        $this->filesystem->write('foobarbuz.txt', 'data');
        $this->filesystem->write('foo', 'data');

        $allKeys = $this->filesystem->listKeys(' ');
        //empty pattern results in ->keys call
        $this->assertEquals(
            $this->filesystem->keys(),
            $allKeys['keys']
        );

        //these values are canonicalized to avoid wrong order or keys issue

        $keys = $this->filesystem->listKeys('foo');
        $this->assertEquals(
            $this->filesystem->keys(),
            $keys['keys'],
            '', 0, 10, true);

        $keys = $this->filesystem->listKeys('foo/foob');
        $this->assertEquals(
            array('foo/foobar/bar.txt'),
            $keys['keys'],
            '', 0, 10, true);

        $keys = $this->filesystem->listKeys('foo/');
        $this->assertEquals(
            array('foo/foobar/bar.txt', 'foo/bar/buzz.txt'),
            $keys['keys'],
            '', 0, 10, true);

        $keys = $this->filesystem->listKeys('foo');
        $this->assertEquals(
            array('foo/foobar/bar.txt', 'foo/bar/buzz.txt', 'foobarbuz.txt', 'foo'),
            $keys['keys'],
            '', 0, 10, true);

        $keys = $this->filesystem->listKeys('fooz');
        $this->assertEquals(
            array(),
            $keys['keys'],
            '', 0, 10, true);
    }
    
    /**
     * @test
     * Tests metadata written to GridFS can be retrieved after writing
     */
    public function testMetadataRetrieveAfterWrite()
    {
        //Create local copy of fileadapter
        $fileadpt = clone $this->filesystem->getAdapter();
        
        $this->filesystem->getAdapter()->setMetadata('metadatatest', array('testing'  =>  true));
        $this->filesystem->write('metadatatest', 'test');
        
        $this->assertEquals($this->filesystem->getAdapter()->getMetadata('metadatatest'), $fileadpt->getMetadata('metadatatest'));
    }
}
