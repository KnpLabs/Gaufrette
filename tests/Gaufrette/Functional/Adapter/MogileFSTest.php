<?php

namespace Gaufrette\Functional\Adapter;

class MogileFSTest extends FunctionalTestCase
{
    /**
     * @group functional
     */
    public function shouldGetMtime()
    {
        $this->markTestSkipped('Not supported by the adapter.');
    }

    /**
     * @group functional
     */
    public function shouldGetMtimeNonExistingFile()
    {
        $this->markTestSkipped('Not supported by the adapter.');
    }
}
