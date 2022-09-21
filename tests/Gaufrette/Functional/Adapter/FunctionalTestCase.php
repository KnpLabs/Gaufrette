<?php

namespace Gaufrette\Functional\Adapter;

use Gaufrette\Exception\FileNotFound;
use Gaufrette\Filesystem;
use PHPUnit\Framework\TestCase;

abstract class FunctionalTestCase extends TestCase
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function getAdapterName()
    {
        if (!preg_match('/\\\\(\w+)Test$/', get_class($this), $matches)) {
            throw new \RuntimeException(sprintf(
                'Unable to guess filesystem name from class "%s", ' .
                'please override the ->getAdapterName() method.',
                get_class($this)
            ));
        }

        return $matches[1];
    }

    protected function setUp(): void
    {
        $basename = $this->getAdapterName();
        $filename = sprintf(
            '%s/adapters/%s.php',
            dirname(__DIR__),
            $basename
        );

        if (!file_exists($filename)) {
            $this->markTestSkipped(
                <<<EOF
To run the {$basename} filesystem tests, you must:

 1. Copy the file "{$filename}.dist" as "{$filename}"
 2. Modify the copied file to fit your environment
EOF
            );
        }

        $adapter = include $filename;
        $this->filesystem = new Filesystem($adapter);
    }

    protected function tearDown(): void
    {
        if (null === $this->filesystem) {
            return;
        }

        $this->filesystem = null;
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWriteAndRead(): void
    {
        $this->assertEquals(12, $this->filesystem->write('foo', 'Some content'));
        $this->assertEquals(13, $this->filesystem->write('test/subdir/foo', 'Some content1', true));

        $this->assertEquals('Some content', $this->filesystem->read('foo'));
        $this->assertEquals('Some content1', $this->filesystem->read('test/subdir/foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldUpdateFileContent(): void
    {
        $this->filesystem->write('foo', 'Some content');
        $this->filesystem->write('foo', 'Some content updated', true);

        $this->assertEquals('Some content updated', $this->filesystem->read('foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldCheckIfFileExists(): void
    {
        $this->assertFalse($this->filesystem->has('foo'));

        $this->filesystem->write('foo', 'Some content');

        $this->assertTrue($this->filesystem->has('foo'));
        $this->assertFalse($this->filesystem->has('test/somefile'));
        $this->assertFalse($this->filesystem->has('test/somefile'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetMtime(): void
    {
        $this->filesystem->write('foo', 'Some content');

        $this->assertGreaterThan(0, $this->filesystem->mtime('foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldFailWhenTryMtimeForKeyWhichDoesNotExist(): void
    {
        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('The file "foo" was not found.');
        $this->filesystem->mtime('foo');
    }

    /**
     * @test
     * @group functional
     */
    public function shouldRenameFile(): void
    {
        $this->filesystem->write('foo', 'Some content');
        $this->filesystem->rename('foo', 'boo');

        $this->assertFalse($this->filesystem->has('foo'));
        $this->assertEquals('Some content', $this->filesystem->read('boo'));
        $this->filesystem->delete('boo');

        $this->filesystem->write('foo', 'Some content');
        $this->filesystem->rename('foo', 'somedir/sub/boo');

        $this->assertFalse($this->filesystem->has('somedir/sub/foo'));
        $this->assertEquals('Some content', $this->filesystem->read('somedir/sub/boo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldDeleteFile(): void
    {
        $this->filesystem->write('foo', 'Some content');

        $this->assertTrue($this->filesystem->has('foo'));

        $this->filesystem->delete('foo');

        $this->assertFalse($this->filesystem->has('foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldFetchKeys(): void
    {
        $this->assertEquals([], $this->filesystem->keys());

        $this->filesystem->write('foo', 'Some content');
        $this->filesystem->write('bar', 'Some content');
        $this->filesystem->write('baz', 'Some content');

        $actualKeys = $this->filesystem->keys();

        $this->assertCount(3, $actualKeys);
        foreach (['foo', 'bar', 'baz'] as $key) {
            $this->assertContains($key, $actualKeys);
        }
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWorkWithHiddenFiles(): void
    {
        $this->filesystem->write('.foo', 'hidden');
        $this->assertTrue($this->filesystem->has('.foo'));
        $this->assertContains('.foo', $this->filesystem->keys());
        $this->filesystem->delete('.foo');
        $this->assertFalse($this->filesystem->has('.foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldKeepFileObjectInRegister(): void
    {
        $FileObjectA = $this->filesystem->createFile('somefile');
        $FileObjectB = $this->filesystem->createFile('somefile');

        $this->assertSame($FileObjectA, $FileObjectB);
    }

    /**
     * @test
     * @group functional
     */
    public function shouldWriteToSameFile(): void
    {
        $FileObjectA = $this->filesystem->createFile('somefile');
        $FileObjectA->setContent('ABC');

        $FileObjectB = $this->filesystem->createFile('somefile');
        $FileObjectB->setContent('DEF');

        $this->assertEquals('DEF', $FileObjectA->getContent());
    }
}
