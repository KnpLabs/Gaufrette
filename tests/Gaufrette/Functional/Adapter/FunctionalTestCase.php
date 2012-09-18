<?php

namespace Gaufrette\Functional\Adapter;

abstract class FunctionalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $adapter;

    public function getAdapterName()
    {
        if (!preg_match('/\\\\(\w+)Test$/', get_class($this), $matches)) {
            throw new \RuntimeException(sprintf(
                'Unable to guess adapter name from class "%s", '.
                'please override the ->getAdapterName() method.',
                get_class($this)
            ));
        }

        return $matches[1];
    }

    public function setUp()
    {
        $basename = $this->getAdapterName();
        $filename = sprintf(
            '%s/adapters/%s.php',
            dirname(__DIR__),
            $basename
        );

        if (!file_exists($filename)) {
            return $this->markTestSkipped(<<<EOF
To run the {$basename} adapter tests, you must:

 1. Copy the file "{$filename}.dist" as "{$filename}"
 2. Modify the copied file to fit your environment
EOF
            );
        }

        $this->adapter = include $filename;
    }

    public function tearDown()
    {
        if (null === $this->adapter) {
            return;
        }

        $this->adapter = null;
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWriteAndRead()
    {
        $this->assertEquals(12, $this->adapter->write('foo', 'Some content'));

        $this->assertEquals('Some content', $this->adapter->read('foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldCheckIfFileExists()
    {
        $this->assertFalse($this->adapter->exists('foo'));

        $this->adapter->write('foo', 'Some content');

        $this->assertTrue($this->adapter->exists('foo'));
    }

    /**
     * @test
     * @group functional
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function shouldFailWhenReadNonExistingFile()
    {
        $this->adapter->read('foo');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldCalculateValidChecksum()
    {
        $this->adapter->write('foo', 'Some content');

        $this->assertEquals(md5('Some content'), $this->adapter->checksum('foo'));
    }

    /**
     * @test
     * @group functional
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function shouldFailWhenChecksumNonExistingFile()
    {
        $this->adapter->checksum('foo');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetMtime()
    {
        $this->adapter->write('foo', 'Some content');

        $this->assertEquals(time(), $this->adapter->mtime('foo'), null, 1);
    }

    /**
     * @test
     * @group functional
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function shouldFailWhenGetMtimeNonExistingFile()
    {
        $this->adapter->mtime('foo');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldRenameFile()
    {
        $this->adapter->write('foo', 'Some content');
        $this->adapter->rename('foo', 'boo');

        $this->assertFalse($this->adapter->exists('foo'));
        $this->assertEquals('Some content', $this->adapter->read('boo'));
        $this->adapter->delete('boo');
    }

    /**
     * @test
     * @group functional
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function shouldFailWhenRenameNonExistingFile()
    {
        $this->adapter->rename('foo', 'bar');
    }

    /**
     * @test
     * @group functional
     * @expectedException Gaufrette\Exception\UnexpectedFile
     */
    public function shouldRenameToAlreadyExistingFile()
    {
        $this->adapter->write('foo', 'Some content');
        $this->adapter->write('bar', 'Some content');

        $this->adapter->rename('foo', 'bar');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldDeleteFile()
    {
        $this->adapter->write('foo', 'Some content');

        $this->assertTrue($this->adapter->exists('foo'));

        $this->adapter->delete('foo');

        $this->assertFalse($this->adapter->exists('foo'));
    }

    /**
     * @test
     * @group functional
     * @expectedException Gaufrette\Exception\FileNotFound
     */
    public function shouldFailDeleteNonExistingFile()
    {
        $this->adapter->delete('foo');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldFetchKeys()
    {
        $this->assertEquals(array(), $this->adapter->keys());

        $this->adapter->write('foo', 'Some content');
        $this->adapter->write('bar', 'Some content');
        $this->adapter->write('baz', 'Some content');

        $actualKeys = $this->adapter->keys();

        $this->assertEquals(3, count($actualKeys));
        foreach (array('foo', 'bar', 'baz') as $key) {
            $this->assertContains($key, $actualKeys);
        }
    }
}
