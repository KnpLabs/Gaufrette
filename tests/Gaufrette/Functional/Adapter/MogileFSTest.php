<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\MogileFS;

class MogileFSTest extends FunctionalTestCase
{
    /**
     * @group functional
     */
    public function shouldGetMtime()
    {
        $this->markTestSkipped('Not supported by the adpater.');
    }

    /**
     * @group functional
     */
    public function shouldGetMtimeNonExistingFile()
    {
        $this->markTestSkipped('Not supported by the adpater.');
    }
}
