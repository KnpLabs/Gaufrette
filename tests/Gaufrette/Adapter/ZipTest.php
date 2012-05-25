<?php

namespace Gaufrette\Adapter;

class ZipTest extends FunctionalTestCase
{
    public function setUp()
    {
        if (!extension_loaded('zip')) {
            return $this->markTestSkipped('The zip extension is not available.');
        }

        @touch(__DIR__ . '/test.zip');

        $this->adapter = new Zip(__DIR__ . '/test.zip');
    }

    public function tearDown()
    {
        if (null === $this->adapter) {
            return;
        }

        $this->adapter = null;

        @unlink(__DIR__ . '/test.zip');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInvalidZipArchiveThrowRuntimeException()
    {
        new Zip(__FILE__);
    }

    public function testNotSupportingMetadata()
    {
        $this->assertFalse($this->adapter->supportsMetadata());
    }

    public function testCreateNewZipArchive()
    {
        $tmp = tempnam(sys_get_temp_dir(), uniqid());
        $za = new Zip($tmp);

        $this->assertFileExists($tmp);

        return $za;
    }
}
