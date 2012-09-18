<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\MogileFS;

class MogileFSTest extends FunctionalTestCase
{
    /**
     * @group functional
     */
    public function testMtime()
    {
        $this->markTestSkipped('Not supported by the adpater.');
    }

    /**
     * @group functional
     */
    public function testMtimeNonExistingFile()
    {
        $this->markTestSkipped('Not supported by the adpater.');
    }
}
