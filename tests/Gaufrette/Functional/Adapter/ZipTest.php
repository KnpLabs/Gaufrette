<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\Zip;
use Gaufrette\Filesystem;

class ZipTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('The zip extension is not available.');
        } elseif (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            $this->markTestSkipped('Zip adapter is not supported on Windows.');
        }

        $this->filesystem = new Filesystem(new Zip(__DIR__ . '/test.zip'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        @unlink(__DIR__ . '/test.zip');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldNotAcceptInvalidZipArchive(): void
    {
        $this->expectException(\RuntimeException::class);
        new Zip(__FILE__);
    }
}
