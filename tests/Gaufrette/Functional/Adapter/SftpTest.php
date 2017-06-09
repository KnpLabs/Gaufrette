<?php

namespace Gaufrette\Functional\Adapter;

class SftpTest extends FunctionalTestCase
{
    public function setUp()
    {
        if (!extension_loaded('ssh2')) {
            $this->markTestSkipped('Extension ssh2 not loaded');
        }

        return parent::setUp();
    }
}
