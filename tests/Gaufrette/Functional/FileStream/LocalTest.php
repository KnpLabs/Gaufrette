<?php

namespace Gaufrette\Functional\FileStream;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local as LocalAdapter;

class LocalTest extends FunctionalTestCase
{
    protected $directory;

    public function setUp()
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('gaufrette-tests');

        @mkdir($this->directory . DIRECTORY_SEPARATOR . 'subdir', 0777, true);
        umask(0002);
        $this->filesystem = new Filesystem(new LocalAdapter($this->directory, 0770));

        $this->registerLocalFilesystemInStream();
    }

    public function testDirectoryChmod()
    {
        if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
            $this->markTestSkipped('Chmod and umask are not available on Windows.');
        }

        $r = fopen('gaufrette://filestream/foo/bar', 'a+');
        fclose($r);

        $perms = fileperms($this->directory . '/foo/');
        $this->assertEquals('0770', substr(sprintf('%o', $perms), -4));
    }

    public function tearDown()
    {
        $adapter = $this->filesystem->getAdapter();

        $keys = $this->filesystem->keys();

        // sort keys by length DESC
        usort($keys, static function ($a, $b) {
            if ($a === $b) {
                return 0;
            }

            return strlen($a) > strlen($b) ? -1 : 1;
        });

        foreach ($keys as $key) {
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
