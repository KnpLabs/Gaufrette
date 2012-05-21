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

    public function testKeys()
    {
        $data = array(
            "size"         => "0 bytes",
            "hash"         => "37eb1ba1849d4b0fb0b28caf7ef3af52",
            "bytes"        => 0,
            "thumb_exists" => false,
            "rev"          => "714f029684fe",
            "modified"     => "Wed, 27 Apr 2011 22:18:51 +0000",
            "path"         => "/Public",
            "is_dir"       => true,
            "icon"         => "folder_public",
            "root"         => "dropbox",
            "revision"     => 29007,
            "contents"     => array(
                array(
                    "size"         => "0 bytes",
                    "rev"          => "35c1f029684fe",
                    "thumb_exists" => false,
                    "bytes"        => 0,
                    "modified"     => "Mon, 18 Jul 2011 20:13:43 +0000",
                    "client_mtime" => "Wed, 20 Apr 2011 16:20:19 +0000",
                    "path"         => "/Public/newest",
                    "is_dir"       => true,
                    "icon"         => "page_white_text",
                    "root"         => "dropbox",
                    "mime_type"    => "text/plain",
                    "revision"     => 220191,
                ),
                array(
                    "size"         => "0 bytes",
                    "rev"          => "35c1f029684fe",
                    "thumb_exists" => false,
                    "bytes"        => 0,
                    "modified"     => "Mon, 18 Jul 2011 20:13:43 +0000",
                    "client_mtime" => "Wed, 20 Apr 2011 16:20:19 +0000",
                    "path"         => "/Public/latest.txt",
                    "is_dir"       => false,
                    "icon"         => "page_white_text",
                    "root"         => "dropbox",
                    "mime_type"    => "text/plain",
                    "revision"     => 220191,
                )
            )
        );

        $this->dropbox->expects($this->once())
             ->method('getMetadata')
             ->will($this->returnValue($data));

        $keys = $this->adapter->keys();

        $this->assertCount(2, $keys);
        $this->assertEquals(array('Public/newest', "Public/latest.txt"), $keys);
    }
}
