<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Filesystem;

class GridFSTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    public function setUp()
    {
        if (!class_exists('\Mongo')) {
            $this->markTestSkipped('Mongo class not found.');
        }

        $mongo = new \Mongo($_SERVER['MONGO_SERVER']);

        if (!$mongo->connected) {
            $this->markTestSkipped(sprintf(
                'Cannot connect to Mongo server (%s).',
                $_SERVER['MONGO_SERVER']
            ));
        }

        $db = $mongo->selectDB($_SERVER['MONGO_DATABASE']);

        $grid = $db->getGridFS();
        $grid->remove();

        $this->adapter = new GridFS($grid);
    }

    public function testWriteReadDelete()
    {
        $this->assertFalse($this->adapter->exists('foo'));
        $this->adapter->write('foo', 'The content of foo');
        $this->assertTrue($this->adapter->exists('foo'));
        $this->assertEquals('The content of foo', $this->adapter->read('foo'));
        $this->assertEquals(md5('The content of foo'), $this->adapter->checksum('foo'));
        $this->assertEquals(time(), $this->adapter->mtime('foo'), null, 1);
        $this->adapter->rename('foo', 'bar');
        $this->assertFalse($this->adapter->exists('foo'));
        $this->assertTrue($this->adapter->exists('bar'));
        $this->assertEquals('The content of foo', $this->adapter->read('bar'));
        $this->adapter->delete('bar');
        $this->assertFalse($this->adapter->exists('bar'));
    }
}
