<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\SafeLocal;
use Gaufrette\Functional\LocalDirectoryDeletor;

class SafeLocalTest extends FunctionalTestCase
{
    public function setUp()
    {
        if (!file_exists($this->getDirectory())) {
            mkdir($this->getDirectory());
        }

        $this->filesystem = new Filesystem(new SafeLocal($this->getDirectory()));
    }

    public function tearDown()
    {
        $this->filesystem = null;

        LocalDirectoryDeletor::deleteDirectory($this->getDirectory());
    }

    private function getDirectory()
    {
        return sprintf('%s/filesystem', __DIR__);
    }
}
