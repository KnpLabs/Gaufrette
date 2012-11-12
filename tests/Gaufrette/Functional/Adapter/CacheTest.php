<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Cache;
use Gaufrette\Adapter\InMemory;

class CacheTest extends FunctionalTestCase
{
    public function setUp()
    {
        $this->filesystem = new Filesystem(new Cache(new InMemory(), new InMemory()));
    }
}
