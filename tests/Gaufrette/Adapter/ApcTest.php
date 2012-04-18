<?php

namespace Gaufrette\Adapter;

class ApcTest extends FunctionalTestCase
{
    protected function setUp()
    {
        if (!extension_loaded('apc')) {
            return $this->markTestSkipped('The APC extension is not available.');
        } elseif (!ini_get('apc.enabled') || !ini_get('apc.enable_cli')) {
            return $this->markTestSkipped('The APC extension is available, but not enabled.');
        }

        apc_clear_cache('user');

        $this->adapter = new Apc('gaufrette-test.');
    }

    protected function tearDown()
    {
        if (null === $this->adapter) {
            return;
        }

        apc_clear_cache('user');

        $this->adapter = null;
    }

    public function testSupportsMetadata()
    {
        $this->assertFalse($this->adapter->supportsMetadata());
    }
}
