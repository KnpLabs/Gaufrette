<?php

namespace Gaufrette\Adapter;

class ZipTest extends \PHPUnit_Framework_TestCase
{
    private $filesystem;

    public function setUp()
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('The zip extension is not available.');
        }

        $this->filesystem = new Zip(__DIR__ . '/fixtures/adapter.zip');
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessage Not a zip archive.
     */
    public function testInvalidZipArchiveThrowRuntimeException()
    {
        new Zip(__FILE__);
    }

    public function testGetStat()
    {
        $stat = $this->filesystem->getStat('bar/far/boo.txt');
        $this->assertCount(7, $stat);
    }

    /**
     * @depends testGetStat
     */
    public function testExists()
    {
        $this->assertTrue($this->filesystem->exists('bar/far/boo.txt'));
    }

    /**
     * @depends testGetStat
     */
    public function testNotExists()
    {
        $this->assertFalse($this->filesystem->exists('in/exist/ing/file'));
    }

    public function testKeys()
    {
        $exceptedKeys = array(
            'bar/',
            'bar/far/',
            'bar/far/boo.txt',
            'empty/',
            'foo.txt',
            'geek.gif',
        );

        $this->assertSame($exceptedKeys, $this->filesystem->keys());
    }

    /**
     * @depends testKeys
     * @depends testGetStat
     *
     * @dataProvider getChecksumData
     */
    public function testChecksum($filename, $expected)
    {
        $this->assertEquals($expected, $this->filesystem->checksum($filename));
    }

    public function getChecksumData()
    {
        return array(
            array('bar/far/boo.txt', '19d59dc3323f18b76fa6e9f24e7ca343'),
            array('foo.txt', 'c1693cc0335be737c0acbdcbd1ccae28'),
            array('geek.gif', '07d496967969a8b2b30e4df7e21a393b'),
        );
    }

    /**
     * @depends testChecksum
     */
    public function testRead()
    {
        // We except 2 blank lines at the end of the file
        $this->assertEquals("http://borisguery.com\n\n", $this->filesystem->read('foo.txt'));
    }

    /**
     * @depends testRead
     */
    public function testWriteAndRead()
    {
        $tmp = tempnam(sys_get_temp_dir(), uniqid());
        copy(__DIR__ . '/fixtures/adapter.zip', $tmp);

        $content = 'Hello Fucking World!';

        $za = new Zip($tmp);
        $writtenBytes = $za->write('in/exist/ing/directory/foo.txt', $content);

        $this->assertSame($writtenBytes, mb_strlen($content));
        $this->assertEquals($content, $za->read('in/exist/ing/directory/foo.txt'));
        $this->assertEquals(md5($content), $za->checksum('in/exist/ing/directory/foo.txt'));

        unlink($tmp);
    }

    public function testMtime()
    {
        $tmp = tempnam(sys_get_temp_dir(), uniqid());
        copy(__DIR__ . '/fixtures/adapter.zip', $tmp);

        $adapter = new Zip($tmp);
        $adapter->write('foo', 'Hello world');

        $this->assertEquals(time(), $adapter->mtime('foo'), null, 1);
    }

    public function testDelete()
    {
        $tmp = tempnam(sys_get_temp_dir(), uniqid());
        copy(__DIR__ . '/fixtures/adapter.zip', $tmp);

        $za = new Zip($tmp);
        $za->delete('geek.gif');

        $this->assertFalse($za->getStat('geek.gif', false));

        unlink($tmp);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to delete
     */
    public function testDeleteAnInexistingFileThrowARuntimeException()
    {
        $this->filesystem->delete('in/exist/ing/directory/foo.txt');
    }

    public function testRename()
    {
        $tmp = tempnam(sys_get_temp_dir(), uniqid());
        copy(__DIR__ . '/fixtures/adapter.zip', $tmp);

        $za = new Zip($tmp);
        $za->rename('geek.gif', 'nerd.gif');

        $stat = $za->getStat('nerd.gif', false);
        $this->assertNotEmpty($stat);
        $this->assertArrayHasKey('name', $stat);
        $this->assertEquals('nerd.gif', $stat['name']);

        unlink($tmp);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to rename
     */
    public function testRenameAnInexistingFileThrowARuntimeException()
    {
        $this->filesystem->rename('in/exist/ing/directory/foo.txt', 'that is not important');
    }

    public function testNotSupportingMetadata()
    {
        $this->assertFalse($this->filesystem->supportsMetadata());
    }

    public function testCreateNewZipArchive()
    {
        $tmp = tempnam(sys_get_temp_dir(), uniqid());
        $za = new Zip($tmp);

        $this->assertFileExists($tmp);

        return $za;
    }

    /**
     * @depends testCreateNewZipArchive
     */
    public function testNewlyCreatedZipArchiveWrite($za)
    {
        $writtenBytes = $za->write('foo.txt', 'Bonjour le monde!');

        $this->assertSame($writtenBytes, mb_strlen('Bonjour le monde!'));

        return $za;
    }

    /**
     * @depends testNewlyCreatedZipArchiveWrite
     */
    public function testNewlyCreatedZipArchiveRead($za)
    {
        $this->assertEquals('Bonjour le monde!', $za->read('foo.txt'));
        $this->assertEquals(md5('Bonjour le monde!'), $za->checksum('foo.txt'));
    }
}
