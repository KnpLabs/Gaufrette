<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter\Local;

require_once __DIR__ . '/AbstractFunctionalTest.php';

class LocalFunctionalTest extends AbstractFunctionalTest
{
    protected $directory;

    public function setUp()
    {
        $this->directory = __DIR__.'/filesystem';

        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    public function tearDown()
    {
        if (!is_dir($this->directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->directory,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS
            )
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir(strval($item));
            } else {
                unlink(strval($item));
            }
        }
    }

    protected function getAdapter()
    {
        return new Local($this->directory);
    }
}
