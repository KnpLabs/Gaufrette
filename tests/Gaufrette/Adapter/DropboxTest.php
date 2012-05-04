<?php

namespace Gaufrette\Adapter;

/**
 * Dropbox testcase
 *
 * @package Gaufrette
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class DropboxTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dropbox;

    /**
     * @var Dropbox
     */
    protected $adapter;

    protected function setup()
    {
        $this->dropbox = $this->getMockBuilder('Dropbox_API')->disableOriginalConstructor()->getMock();
        $this->adapter = new Dropbox($this->dropbox);
    }

    public function testWriteFile()
    {
        $content = str_repeat('foobar', 100);

        $this->dropbox->expects($this->once())
             ->method('putFile')
             ->will($this->returnValue(true));

        $this->assertEquals(strlen($content), $this->adapter->write('foobar', $content));
    }

    public function testReadFile()
    {
        $this->dropbox->expects($this->once())
             ->method('getFile')
             ->with($this->equalTo('foo'))
             ->will($this->returnValue('foobar'));

        $this->assertEquals('foobar', $this->adapter->read('foo'));
    }

    public function testDeleteFile()
    {
        $this->dropbox->expects($this->once())
             ->method('delete')
             ->will($this->returnValue(array(
                "size"         => "0 bytes",
                "is_deleted"   => true,
                "bytes"        => 0,
                "thumb_exists" => false,
                "rev"          => "1f33043551f",
                "modified"     => "Wed, 10 Aug 2011 18:21:30 +0000",
                "path"         => "/test.txt",
                "is_dir"       => false,
                "icon"         => "page_white_text",
                "root"         => "dropbox",
                "mime_type"    => "text/plain",
                "revision"     => 492341
            )));

        $this->assertNull($this->adapter->delete('test.txt'));
    }

    public function testExists()
    {
        $this->dropbox->expects($this->once())
             ->method('search')
             ->will($this->returnValue(array(
                 array(
                     "size"         => "0 bytes",
                     "rev"          => "35c1f029684fe",
                     "thumb_exists" => false,
                     "bytes"        => 0,
                     "modified"     => "Mon, 18 Jul 2011 20:13:43 +0000",
                     "path"         => "/Public/latest.txt",
                     "is_dir"       => false,
                     "icon"         => "page_white_text",
                     "root"         => "dropbox",
                     "mime_type"    => "text/plain",
                     "revision"     => 220191
                 )
             )
            ));

        $this->assertEquals(true, $this->adapter->exists('foo'));
    }

    public function testMtime()
    {
        $mtime = strtotime('Tue, 19 Jul 2011 21:55:38 +0000');
        $this->dropbox->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue(array(
                "size"         => "225.4KB",
                "rev"          => "35e97029684fe",
                "thumb_exists" => false,
                "bytes"        => 230783,
                "modified"     => "Tue, 19 Jul 2011 21:55:38 +0000",
                "client_mtime" => "Mon, 18 Jul 2011 18:04:35 +0000",
                "path"         => "/Getting_Started.pdf",
                "is_dir"       => false,
                "icon"         => "page_white_acrobat",
                "root"         => "dropbox",
                "mime_type"    => "application/pdf",
                "revision"     => 220823
        )));

        $this->assertEquals($mtime, $this->adapter->mtime('foobar'));
    }

    public function testChecksum()
    {
        $checksum = md5('foobar');
        $this->dropbox->expects($this->once())
             ->method('getFile')
             ->will($this->returnValue('foobar'));

        $this->assertEquals($checksum, $this->adapter->checksum('foobar'));
    }

    public function testRealTestCase()
    {
        if (!isset($_SERVER['DROPBOX_API_CONSUMER_KEY']) || !isset($_SERVER['DROPBOX_API_CONSUMER_SECRET'])) {
            $this->markTestSkipped('');
        }

        $oauth = new \Dropbox_OAuth_Curl($_SERVER['DROPBOX_API_CONSUMER_KEY'], $_SERVER['DROPBOX_API_CONSUMER_SECRET']);
        $api = new \Dropbox_API($oauth);
        $adapter = new Dropbox($api);

        $file = 'foobar.txt';
        $content = 'This is a example content';

        $this->assertEquals(strlen($content), $adapter->write($file, $content));

        $this->assertTrue($adapter->exists($file));
        $this->assertEquals($content, $adapter->read($file));

        $adapter->rename($file, $fileNew = 'foobar_new.txt');
        $this->assertFalse($adapter->exists($file));
        $this->assertTrue($adapter->exists($file));
        $this->assertEquals($content, $adapter->read($fileNew));

        $this->assertNull($adapter->delete($fileNew));
        $this->assertFalse($adapter->exists($fileNew));
    }
}
