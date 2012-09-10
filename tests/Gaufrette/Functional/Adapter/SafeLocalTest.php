<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Adapter\SafeLocal;

class SafeLocalTest extends FunctionalTestCase
{
    public function setUp()
    {
        if (!file_exists($this->getDirectory())) {
            mkdir($this->getDirectory());
        }

        $this->adapter = new SafeLocal($this->getDirectory());
    }

    public function tearDown()
    {
        $this->adapter = null;

        if (file_exists($this->getDirectory())) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->getDirectory(),
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
    }

    /**
     * @dataProvider getKeyPathData
     */
    public function testComputeKey($key, $path)
    {
        $this->assertEquals($key, $this->adapter->computeKey($path));
    }

    /**
     * @dataProvider getKeyPathData
     */
    public function testComputePath($key, $path)
    {
        $this->assertEquals($path, $this->adapter->computePath($key));
    }

    public function getKeyPathData()
    {
        return array(
            array(
                '../../..',
                $this->getDirectory() . '/Li4vLi4vLi4='
            ),
            array(
                'foo/bar',
                $this->getDirectory() . '/Zm9vL2Jhcg=='
            ),
            array(
                'foo/foo/../bar',
                $this->getDirectory() . '/Zm9vL2Zvby8uLi9iYXI='
            ),
            array(
                'foo_bar',
                $this->getDirectory() . '/Zm9vX2Jhcg=='
            )
        );
    }

    private function getDirectory()
    {
        return sprintf('%s/filesystem', __DIR__);
    }
}
