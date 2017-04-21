<?php

namespace Gaufrette\Adapter\Local\Functional\FileStream;

use Gaufrette\Adapter\Local\Local;
use Gaufrette\Filesystem;
use Gaufrette\Functional\FileStream\FunctionalTestCase;

class LocalTest extends FunctionalTestCase
{
    protected $directory;

    public function setUp()
    {
        $this->directory = __DIR__.DIRECTORY_SEPARATOR.'filesystem';
        @mkdir($this->directory.DIRECTORY_SEPARATOR.'subdir', 0777, true);
        $this->filesystem = new Filesystem(new Local($this->directory, true));

        $this->registerLocalFilesystemInStream();
    }

    public function tearDown()
    {
        if (is_dir($this->directory)) {
            (new \Symfony\Component\Filesystem\Filesystem())->remove($this->directory);
        }
    }

    /**
     * @test
     */
    public function shouldSupportsDirectory()
    {
        $this->assertTrue(file_exists('gaufrette://filestream/subdir'));
        $this->assertTrue(is_dir('gaufrette://filestream/subdir'));
    }
}
