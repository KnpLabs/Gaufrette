<?php

namespace Gaufrette\Adapter;

class ApcTest extends \PHPUnit_Framework_TestCase
{
    const PREFIX = 'test-suite.';
    const KEY = 'test-key';
    const CONTENT = 'Yummy, some test content!';

    protected function setUp()
    {
        if (!extension_loaded('apc')) {
            $this->markTestSkipped('The APC extension is not available.');
        }

        if (!(ini_get('apc.enabled') && ini_get('apc.enable_cli'))) {
            $this->markTestSkipped('The APC extension is available, but not enabled.');
        } else {
            apc_clear_cache('user');
        }
    }

    protected function tearDown()
    {
        if (ini_get('apc.enabled') && ini_get('apc.enable_cli')) {
            apc_clear_cache('user');
        }
    }

    public function testComputePath()
    {
        $adapter = new Apc(self::PREFIX);

        $this->assertEquals(self::PREFIX . 'foobar', $adapter->computePath('foobar'));
    }

    /**
     * @depends testComputePath
     */
    public function testWriteAndRead()
    {
        $adapter = new Apc(self::PREFIX);
        $adapter->write(self::KEY, self::CONTENT);

        $this->assertEquals(self::CONTENT, $adapter->read(self::KEY));
    }

    /**
     * @depends testWriteAndRead
     */
    public function testExists()
    {
        $adapter = new Apc(self::PREFIX);
        $adapter->write(self::KEY, null);

        $this->assertTrue($adapter->exists(self::KEY));
        $this->assertFalse($adapter->exists('non-existing-key'));
    }

    /**
     * @depends testWriteAndRead
     */
    public function testKeys()
    {
        $adapter = new Apc(self::PREFIX);
        $adapter->write('test-key1', null);
        $adapter->write('test-key2', null);
        $adapter->write(self::PREFIX, null);

        $this->assertEquals(array('test-key1', 'test-key2', self::PREFIX), $adapter->keys());
    }

    /**
     * @depends testWriteAndRead
     */
    public function testMtime()
    {
        $adapter = new Apc(self::PREFIX);
        $adapter->write(self::KEY, null);

        $this->assertTrue(time() >= $adapter->mtime(self::KEY));
    }

    /**
     * @depends testWriteAndRead
     */
    public function testChecksum()
    {
        $adapter = new Apc(self::PREFIX);
        $adapter->write(self::KEY, self::CONTENT);

        $this->assertEquals(md5(self::CONTENT), $adapter->checksum(self::KEY));
    }

    /**
     * @depends testWriteAndRead
     */
    public function testDelete()
    {
        $adapter = new Apc(self::PREFIX);

        $adapter->write(self::KEY, null);
        $adapter->delete(self::KEY);

        $this->assertFalse($adapter->exists(self::KEY));
    }

    /**
     * @depends testWriteAndRead
     */
    public function testRename()
    {
        $adapter = new Apc(self::PREFIX);
        $adapter->write(self::KEY, null);
        $adapter->rename(self::KEY, 'new-key');

        $this->assertTrue($adapter->exists('new-key'));
        $this->assertFalse($adapter->exists(self::KEY));
    }

    public function testSupportsMetadata()
    {
        $adapter = new Apc(self::PREFIX);

        $this->assertFalse($adapter->supportsMetadata());
    }
}
