<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\Cache;
use Gaufrette\Adapter\InMemory;

class CacheTest extends FunctionalTestCase
{
    public function setUp()
    {
        $this->adapter = new Cache(new InMemory(), new InMemory());
    }

    public function tearDown()
    {
        if (null === $this->adapter) {
            return;
        }

        $this->adapter = null;
    }
}
