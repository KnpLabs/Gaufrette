<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\Apc;
use Gaufrette\Filesystem;

class ApcTest extends FunctionalTestCase
{
    protected function setUp()
    {
        if (!extension_loaded('apc')) {
            return $this->markTestSkipped('The APC extension is not available.');
        } elseif (!filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN) || !filter_var(ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOLEAN)) {
            return $this->markTestSkipped('The APC extension is available, but not enabled.');
        }

        apc_clear_cache('user');

        $this->filesystem = new Filesystem(new Apc('gaufrette-test.'));
    }

    protected function tearDown()
    {
        parent::tearDown();
        if (extension_loaded('apc')) {
            apc_clear_cache('user');
        }
    }
}
