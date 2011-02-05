<?php

namespace Gaufrette\Filesystem\Adapter;

use Gaufrette\Filesystem\Adapter\Local;

require_once __DIR__ . '/AbstractFunctionalTest.php';

class LocalFunctionalTest extends AbstractFunctionalTest
{
    protected static $directory = null;

    public static function setUpBeforeClass()
    {
        if (null === self::$directory) {
            self::$directory = __DIR__ . DIRECTORY_SEPARATOR . 'filesystem';
        }

        if (!is_dir(self::$directory)) {
            @mkdir(self::$directory, 0777, true);
        }
    }

    public static function tearDownAfterClass()
    {
        // skip if there is no directory to remove
        if (null === self::$directory || !is_dir(self::$directory)) {
            return ;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$directory),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir(strval($item));
            }
        }
    }

    protected function getAdapter()
    {
        return new Local(self::$directory);
    }
}
