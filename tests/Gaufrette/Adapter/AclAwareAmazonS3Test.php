<?php

namespace Gaufrette\Adapter;

class AclAwareAmazonS3Test extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!defined('AmazonS3::GRANT_READ')) {
            $this->markTestSkipped('AmazonS3 have to be installed');
        }
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldFailWhenSetUsersWithInvalidPermission()
    {
        $users = array(
            array('permission' => 'invalid', 'group' => 'all')
        );

        $adapter = new AclAwareAmazonS3(
            $this->getMock('Gaufrette\Adapter'),
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );
        $adapter->setUsers($users);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldFailWhenSetUsersWithoutPermission()
    {
        $users = array(
            array('group' => 'all')
        );

        $adapter = new AclAwareAmazonS3(
            $this->getMock('Gaufrette\Adapter'),
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );
        $adapter->setUsers($users);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldFailWhenSetUsersWithInvalidGroup()
    {
        $users = array(
            array('permission' => 'read', 'group' => 'invalid')
        );

        $adapter = new AclAwareAmazonS3(
            $this->getMock('Gaufrette\Adapter'),
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );
        $adapter->setUsers($users);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldFailWhenSetUsersWithoutGroupOrUserId()
    {
        $users = array(
            array('permission' => 'read')
        );

        $adapter = new AclAwareAmazonS3(
            $this->getMock('Gaufrette\Adapter'),
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );
        $adapter->setUsers($users);
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldUpdateAclWithUserDataWhenWrite()
    {
        $users = array(
            array('permission' => 'read', 'group' => 'all')
        );

        $response = new \stdClass;
        $response->status = 200;

        $amazonS3 = $this->getMock('AmazonS3', array('set_object_acl'), array(), '', false);
        $amazonS3->expects($this->once())
            ->method('set_object_acl')
            ->with($this->equalTo('foobar'), $this->equalTo('some'), array(
                array(
                    'permission' => 'READ',
                    'id' => 'http://acs.amazonaws.com/groups/global/AllUsers'
                )
            ))
            ->will($this->returnValue($response));

        $adapter = new AclAwareAmazonS3(
            $this->getMock('Gaufrette\Adapter'),
            $amazonS3,
            'foobar'
        );
        $adapter->setUsers($users);
        $adapter->write('some', 'Some content');
    }

    /**
     * @test
     * @expectedException RuntimeException
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldFailUpdateAclCauseErrorResponseCode()
    {
        $users = array(
            array('permission' => 'read', 'group' => 'all')
        );

        $response = new \stdClass;
        $response->status = 400;

        $amazonS3 = $this->getMock('AmazonS3', array('set_object_acl'), array(), '', false);
        $amazonS3->expects($this->once())
            ->method('set_object_acl')
            ->will($this->returnValue($response));

        $adapter = new AclAwareAmazonS3(
            $this->getMock('Gaufrette\Adapter'),
            $amazonS3,
            'foobar'
        );
        $adapter->write('some', 'Some content');
    }

    /**
     * @test
     * @expectedException RuntimeException
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldDeleteFileWhenCannotUpdateAclOnWrite()
    {
        $users = array(
            array('permission' => 'read', 'group' => 'all')
        );

        $delegateAdapter = $this->getMock('Gaufrette\Adapter');
        $delegateAdapter->expects($this->once())
            ->method('delete')
            ->with($this->equalTo('some'));

        $response = new \stdClass;
        $response->status = 400;

        $amazonS3 = $this->getMock('AmazonS3', array('set_object_acl'), array(), '', false);
        $amazonS3->expects($this->once())
            ->method('set_object_acl')
            ->will($this->returnValue($response));

        $adapter = new AclAwareAmazonS3(
            $delegateAdapter,
            $amazonS3,
            'foobar'
        );
        $adapter->write('some', 'Some content');
    }

    /**
     * @test
     * @expectedException RuntimeException
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldDeleteFileWhenCannotUpdateAclOnRename()
    {
        $users = array(
            array('permission' => 'read', 'group' => 'all')
        );

        $delegateAdapter = $this->getMock('Gaufrette\Adapter');
        $delegateAdapter->expects($this->once())
            ->method('delete')
            ->with($this->equalTo('some'));

        $response = new \stdClass;
        $response->status = 400;

        $amazonS3 = $this->getMock('AmazonS3', array('set_object_acl'), array(), '', false);
        $amazonS3->expects($this->once())
            ->method('set_object_acl')
            ->will($this->returnValue($response));

        $adapter = new AclAwareAmazonS3(
            $delegateAdapter,
            $amazonS3,
            'foobar'
        );
        $adapter->rename('some', 'some2');
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldDelegateWrite()
    {
        $delegateAdapter = $this->getMock('Gaufrette\Adapter');
        $delegateAdapter->expects($this->once())
            ->method('write')
            ->with($this->equalTo('some'), $this->equalTo('some content'))
            ->will($this->returnValue('Success'));

        $response = new \stdClass;
        $response->status = 200;

        $amazonS3 = $this->getMock('AmazonS3', array('set_object_acl'), array(), '', false);
        $amazonS3->expects($this->once())
            ->method('set_object_acl')
            ->will($this->returnValue($response));

        $adapter = new AclAwareAmazonS3(
            $delegateAdapter,
            $amazonS3,
            'foobar'
        );
        $this->assertSame('Success', $adapter->write('some', 'some content'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldDelegateRename()
    {
        $delegateAdapter = $this->getMock('Gaufrette\Adapter');
        $delegateAdapter->expects($this->once())
            ->method('rename')
            ->with($this->equalTo('some'), $this->equalTo('some2'))
            ->will($this->returnValue('Success'));

        $response = new \stdClass;
        $response->status = 200;

        $amazonS3 = $this->getMock('AmazonS3', array('set_object_acl'), array(), '', false);
        $amazonS3->expects($this->once())
            ->method('set_object_acl')
            ->will($this->returnValue($response));

        $adapter = new AclAwareAmazonS3(
            $delegateAdapter,
            $amazonS3,
            'foobar'
        );
        $this->assertSame('Success', $adapter->rename('some', 'some2'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldDelegateExists()
    {
        $delegateAdapter = $this->getMock('Gaufrette\Adapter');
        $delegateAdapter->expects($this->at(0))
            ->method('exists')
            ->with($this->equalTo('some'))
            ->will($this->returnValue(true));
        $delegateAdapter->expects($this->at(1))
            ->method('exists')
            ->with($this->equalTo('some'))
            ->will($this->returnValue(false));

        $adapter = new AclAwareAmazonS3(
            $delegateAdapter,
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );

        $this->assertTrue($adapter->exists('some'));
        $this->assertFalse($adapter->exists('some'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldDelegateRead()
    {
        $delegateAdapter = $this->getMock('Gaufrette\Adapter');
        $delegateAdapter->expects($this->once())
            ->method('read')
            ->with($this->equalTo('some'))
            ->will($this->returnValue('Some content'));

        $adapter = new AclAwareAmazonS3(
            $delegateAdapter,
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );

        $this->assertSame('Some content', $adapter->read('some'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldDelegateMtime()
    {
        $delegateAdapter = $this->getMock('Gaufrette\Adapter');
        $delegateAdapter->expects($this->once())
            ->method('mtime')
            ->with($this->equalTo('some'))
            ->will($this->returnValue('some time'));

        $adapter = new AclAwareAmazonS3(
            $delegateAdapter,
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );

        $this->assertSame('some time', $adapter->mtime('some'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldDelegateChecksum()
    {
        $delegateAdapter = $this->getMock('Gaufrette\Adapter');
        $delegateAdapter->expects($this->once())
            ->method('checksum')
            ->with($this->equalTo('some'))
            ->will($this->returnValue('123aa'));

        $adapter = new AclAwareAmazonS3(
            $delegateAdapter,
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );

        $this->assertSame('123aa', $adapter->checksum('some'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldDelegateKeys()
    {
        $delegateAdapter = $this->getMock('Gaufrette\Adapter');
        $delegateAdapter->expects($this->once())
            ->method('keys')
            ->will($this->returnValue(array('foo', 'bar')));

        $adapter = new AclAwareAmazonS3(
            $delegateAdapter,
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );

        $this->assertSame(array('foo', 'bar'), $adapter->keys());
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldDelegateDelete()
    {
        $delegateAdapter = $this->getMock('Gaufrette\Adapter');
        $delegateAdapter->expects($this->once())
            ->method('delete')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true));

        $adapter = new AclAwareAmazonS3(
            $delegateAdapter,
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );

        $this->assertTrue($adapter->delete('foo'));
    }

    /**
     * @test
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldSetAclConstant()
    {
        $response = new \stdClass;
        $response->status = 200;

        $amazonS3 = $this->getMock('AmazonS3', array('set_object_acl'), array(), '', false);
        $amazonS3->expects($this->once())
            ->method('set_object_acl')
            ->with($this->equalTo('foobar'), $this->equalTo('some'), $this->equalTo('public-read'))
            ->will($this->returnValue($response));

        $adapter = new AclAwareAmazonS3(
            $this->getMock('Gaufrette\Adapter'),
            $amazonS3,
            'foobar'
        );
        $adapter->setAclConstant('public');
        $adapter->write('some', 'Some content');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @covers Gaufrette\Adapter\AclAwareAmazonS3
     */
    public function shouldNotSetUndefinedAclConstant()
    {
        $adapter = new AclAwareAmazonS3(
            $this->getMock('Gaufrette\Adapter'),
            $this->getMock('AmazonS3', array(), array(), '', false),
            'foobar'
        );
        $adapter->setAclConstant('some');
    }
}
