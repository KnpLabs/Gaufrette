<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\SafeLocal;

class SafeLocalTest extends FunctionalTestCase
{
    protected function setUp()
    {
        if (!file_exists($this->getDirectory())) {
            mkdir($this->getDirectory());
        }

        $this->filesystem = new Filesystem(new SafeLocal($this->getDirectory()));
    }

    protected function tearDown()
    {
        foreach ($this->filesystem->keys() as $key) {
            $this->filesystem->delete($key);
        }

        $this->filesystem = null;

        rmdir($this->getDirectory());
    }

    private function getDirectory()
    {
        return sprintf('%s/filesystem', __DIR__);
    }
}
