<?php

namespace Gaufrette\Adapter;

class ApcTest extends \PHPUnit_Framework_TestCase
{
    const PREFIX = 'test-suite.';

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

    public function testStoreAndFetchObject()
    {
        $adapter = new Apc(self::PREFIX);
        $content = 'Yummy, some test content!';
        $key = 'test-key';

        $adapter->storeObject($key, $content);
        $this->assertEquals($content, $adapter->fetchObject($key));
    }

    public function testKeys()
    {
        $adapter = new Apc(self::PREFIX);
        $content = 'Yummy, some test content!';

        $adapter->storeObject('test-key1', $content);
        $adapter->storeObject('test-key2', $content);

        $this->assertEquals(array('test-key1', 'test-key2'), $adapter->keys());
    }
}
