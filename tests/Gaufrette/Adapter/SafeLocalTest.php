<?php

namespace Gaufrette\Adapter;

class SafeLocalTest extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    public function setUp()
    {
        $this->adapter = new SafeLocal(__DIR__);
    }

    public function tearDown()
    {
        $this->adapter = null;
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
                __DIR__ . '/Li4vLi4vLi4='
            ),
            array(
                'foo/bar',
                __DIR__ . '/Zm9vL2Jhcg=='
            ),
            array(
                'foo/foo/../bar',
                __DIR__ . '/Zm9vL2Zvby8uLi9iYXI='
            ),
            array(
                'foo_bar',
                __DIR__ . '/Zm9vX2Jhcg=='
            )
        );
    }
}
