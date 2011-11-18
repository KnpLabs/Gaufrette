<?php

namespace Gaufrette;

class FilesystemMapTest extends \PHPUnit_Framework_TestCase
{
    public function testGetSetHasRemoveAllAndClear()
    {
        $map = new FilesystemMap();
        $this->assertEquals(array(), $map->all(), '->all() returns an empty array when the map is empty');
        $this->assertFalse($map->has('foo'), '->has() returns FALSE when the specified filesystem does NOT exist');
        $map->set('foo', $foo = $this->getFilesystemMock());
        $this->assertTrue($map->has('foo'), '->has() returns TRUE when the specified filesystem exists');
        $this->assertEquals($foo, $map->get('foo'), '->get() returns the specified filesystem');
        $this->assertEquals(array('foo' => $foo), $map->all(), '->all() returns a single valued array when the map contains only one entry');
        $map->remove('foo');
        $this->assertFalse($map->has('foo'), '->remove() removes the filesystem from the map');
        $map->set('a', $a = $this->getFilesystemMock());
        $map->set('b', $b = $this->getFilesystemMock());
        $this->assertEquals(array('a' => $a, 'b' => $b), $map->all(), '->all() returns an array containing all the defined filesystems');
        $map->clear();
        $this->assertEquals(array(), $map->all(), '->clear() removes all the filesystems from the map');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetANonExistingFilesystem()
    {
        $map = new FilesystemMap();
        $map->get('foo');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRemoveANonExistingFilesystem()
    {
        $map = new FilesystemMap();
        $map->remove('foo');
    }

    /**
     * @expectedException InvalidArgumentException
     * @dataProvider getInvalidDomains
     */
    public function testSetUsingAnInvalidDomain($name)
    {
        $map = new FilesystemMap();
        $map->set($name, $this->getFilesystemMock());
    }

    public function getInvalidDomains()
    {
        return array(
            array('with space'),
            array('with/slash'),
            array('with$special*chars')
        );
    }

    private function getFilesystemMock()
    {
        return $this->getMock('Gaufrette\Filesystem', array(), array(), '', false);
    }
}
