<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\Ftp;

class FtpTest extends FunctionalTestCase
{
    /**
     * @test
     * @group functional
     */
    public function shouldWorkWithHiddenFiles()
    {
        $this->filesystem->write('.foo', 'hidden');
        $this->assertTrue($this->filesystem->has('.foo'));
        $this->assertContains('.foo', $this->filesystem->keys());
        $this->filesystem->delete('.foo');
        $this->assertFalse($this->filesystem->has('.foo'));
    }
}
