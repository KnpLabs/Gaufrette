<?php

namespace Gaufrette\Adapter;

class ZipTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Zip
     */
    protected $_zipFixture;

    public function setUp()
    {
        if (!extension_loaded('zip')) {
            $this->markTestSkipped('The zip extension is not available.');
        }

        $this->_zipFixture = new Zip(__DIR__ . '/fixtures/adapter.zip');

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
        $stat = $this->_zipFixture->getStat('bar/far/boo.txt');
        $this->assertCount(7, $stat);
    }

    /**
     * @depends testGetStat
     */
    public function testExists()
    {
        $this->assertTrue($this->_zipFixture->exists('bar/far/boo.txt'));
    }

    /**
     * @depends testGetStat
     */
    public function testNotExists()
    {
        $this->assertFalse($this->_zipFixture->exists('in/exist/ing/file'));
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

        $this->assertSame($exceptedKeys, $this->_zipFixture->keys());
    }

    /**
     * @depends testKeys
     * @depends testGetStat
     */
    public function testChecksum()
    {
        /* Result got from unzip -vl adapter.zip

           Length   Method    Size  Ratio   Date   Time   CRC-32    Name
           --------  ------  ------- -----   ----   ----   ------    ----
                 0  Stored        0   0%  03-30-12 20:22  00000000  bar/
                 0  Stored        0   0%  03-30-12 22:17  00000000  bar/far/
                22  Defl:N       27 -23%  03-30-12 20:18  5d177919  bar/far/boo.txt
                 0  Stored        0   0%  03-30-12 22:17  00000000  empty/
                23  Defl:N       28 -22%  03-30-12 20:15  77d273e4  foo.txt
             15425  Defl:N    15262   1%  03-29-12 11:58  a6d764b9  geek.gif
        */

        $expectedChecksums = array(
            'bar/'            => hexdec('00000000'),
            'bar/far/'        => hexdec('00000000'),
            'bar/far/boo.txt' => hexdec('5d177919'),
            'empty/'          => hexdec('00000000'),
            'foo.txt'         => hexdec('77d273e4'),
            'geek.gif'        => hexdec('a6d764b9'),
        );

        $actualChecksums = array();
        foreach ($this->_zipFixture->keys() as $key) {
            $actualChecksums[$key] = $this->_zipFixture->checksum($key);
        }

        $this->assertEquals($expectedChecksums, $actualChecksums);

    }

    /**
     * @depends testChecksum
     */
    public function testRead()
    {
        // We except 2 blank lines at the end of the file
        $this->assertEquals("http://borisguery.com\n\n", $this->_zipFixture->read('foo.txt'));
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
        $this->assertEquals(crc32($content), $za->checksum('in/exist/ing/directory/foo.txt'));

        unlink($tmp);
    }

    public function testMtime()
    {
        /* Result got from unzip -vl adapter.zip

           Length   Method    Size  Ratio   Date   Time   CRC-32    Name
           --------  ------  ------- -----   ----   ----   ------    ----
                 0  Stored        0   0%  03-30-12 20:22  00000000  bar/
                 0  Stored        0   0%  03-30-12 22:17  00000000  bar/far/
                22  Defl:N       27 -23%  03-30-12 20:18  5d177919  bar/far/boo.txt
                 0  Stored        0   0%  03-30-12 22:17  00000000  empty/
                23  Defl:N       28 -22%  03-30-12 20:15  77d273e4  foo.txt
             15425  Defl:N    15262   1%  03-29-12 11:58  a6d764b9  geek.gif
        */

        $expectedMtimes = array(
            'bar/'            => '03-30-12 20:22',
            'bar/far/'        => '03-30-12 22:17',
            'bar/far/boo.txt' => '03-30-12 20:18',
            'empty/'          => '03-30-12 22:17',
            'foo.txt'         => '03-30-12 20:15',
            'geek.gif'        => '03-29-12 11:58',
        );

        $actualMtimes = array();
        foreach ($this->_zipFixture->keys() as $key) {
            $actualMtimes[$key] = date('m-d-y H:i', $this->_zipFixture->mtime($key));
        }

        $this->assertEquals($expectedMtimes, $actualMtimes);
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
        $this->_zipFixture->delete('in/exist/ing/directory/foo.txt');
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
        $this->_zipFixture->rename('in/exist/ing/directory/foo.txt', 'that is not important');
    }

    public function testNotSupportingMetadata()
    {
        $this->assertFalse($this->_zipFixture->supportsMetadata());
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
        $this->assertEquals(crc32('Bonjour le monde!'), $za->checksum('foo.txt'));
    }
}
