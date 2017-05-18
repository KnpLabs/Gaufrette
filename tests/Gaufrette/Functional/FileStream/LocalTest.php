<?php

namespace Gaufrette\Functional\FileStream;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;
use Gaufrette\Functional\LocalDirectoryDeletor;

class LocalTest extends FunctionalTestCase
{
    protected $directory;

    public function setUp()
    {
        $this->directory = __DIR__.DIRECTORY_SEPARATOR.'filesystem';
        @mkdir($this->directory.DIRECTORY_SEPARATOR.'subdir', 0777, true);
        $this->filesystem = new Filesystem(new LocalAdapter($this->directory, true));

        $this->registerLocalFilesystemInStream();
    }

    public function tearDown()
    {
        LocalDirectoryDeletor::deleteDirectory($this->directory);
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
