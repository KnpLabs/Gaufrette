<?php

namespace Gaufrette\Functional\FileStream;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;

class LocalTest extends FunctionalTestCase
{
    protected $directory;

    protected function setUp()
    {
        $this->directory = __DIR__ . DIRECTORY_SEPARATOR . 'filesystem';
        @mkdir($this->directory . DIRECTORY_SEPARATOR . 'subdir', 0777, true);
        umask(0002);
        $this->filesystem = new Filesystem(new LocalAdapter($this->directory, true, 0770));

        $this->registerLocalFilesystemInStream();
    }

    /**
     * @test
     */
    public function shouldChmodDirectory()
    {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            $this->markTestSkipped('Chmod and umask are not available on Windows.');
        }

        $r = fopen('gaufrette://filestream/foo/bar', 'a+');
        fclose($r);

        $perms = fileperms($this->directory . '/foo/');
        $this->assertEquals('0770', substr(sprintf('%o', $perms), -4));
    }

    protected function tearDown()
    {
        $adapter = $this->filesystem->getAdapter();

        foreach ($this->filesystem->keys() as $key) {
            $adapter->delete($key);
        }

        $this->filesystem = null;

        rmdir($this->directory);
    }

    /**
     * @test
     */
    public function shouldSupportsDirectory()
    {
        $this->assertFileExists('gaufrette://filestream/subdir');
        $this->assertDirectoryExists('gaufrette://filestream/subdir');
    }
}
