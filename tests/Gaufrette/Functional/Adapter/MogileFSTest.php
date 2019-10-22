<?php

namespace Gaufrette\Functional\Adapter;

class MogileFSTest extends FunctionalTestCase
{
    /**
     * @test
     * @group functional
     */
    public function shouldGetMtime()
    {
        $this->markTestSkipped('Not supported by the adapter.');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetMtimeNonExistingFile()
    {
        $this->markTestSkipped('Not supported by the adapter.');
    }
}
