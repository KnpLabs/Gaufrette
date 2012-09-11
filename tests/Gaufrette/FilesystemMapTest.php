<?php

namespace Gaufrette;

class FilesystemMapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldReturnEmptyArrayWhenFilesystemsWasNotSet()
    {
        $map = new FilesystemMap();
        $this->assertEquals(array(), $map->all(), '->all() returns an empty array when the map is empty');
    }

    /**
     * @test
     */
    public function shouldCheckIfFilesystemIsSet()
    {
        $map = new FilesystemMap();
        $this->assertFalse($map->has('foo'), '->has() returns FALSE when the specified filesystem does NOT exist');

        $map->set('foo', $this->getFilesystemMock());
        $this->assertTrue($map->has('foo'), '->has() returns TRUE when the specified filesystem exists');
    }

    /**
     * @test
     */
    public function shouldGetFilesystemWhichWasSet()
    {
        $filesystem = $this->getFilesystemMock();

        $map = new FilesystemMap();
        $map->set('foo', $filesystem);

        $this->assertSame($filesystem, $map->get('foo'), '->get() returns the specified filesystem');
    }

    /**
     * @test
     */
    public function shouldGetAllFilesystems()
    {
        $fooFilesystem = $this->getFilesystemMock();
        $barFilesystem = $this->getFilesystemMock();

        $map = new FilesystemMap();
        $map->set('foo', $fooFilesystem);
        $map->set('bar', $barFilesystem);

        $this->assertSame(array('foo' => $fooFilesystem, 'bar' => $barFilesystem), $map->all());
    }

    /**
     * @test
     */
    public function shouldRemoveFilesystem()
    {
        $fooFilesystem = $this->getFilesystemMock();
        $barFilesystem = $this->getFilesystemMock();

        $map = new FilesystemMap();
        $map->set('foo', $fooFilesystem);
        $map->set('bar', $barFilesystem);
        $map->remove('bar');

        $this->assertSame(array('foo' => $fooFilesystem), $map->all());
    }

    /**
     * @test
     */
    public function shouldClearFilesystems()
    {
        $fooFilesystem = $this->getFilesystemMock();
        $barFilesystem = $this->getFilesystemMock();

        $map = new FilesystemMap();
        $map->set('foo', $fooFilesystem);
        $map->set('bar', $barFilesystem);
        $map->clear();

        $this->assertEquals(array(), $map->all());
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function shouldFailWhenGetANonExistingFilesystem()
    {
        $map = new FilesystemMap();
        $map->get('foo');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function shouldFailWhenRemoveANonExistingFilesystem()
    {
        $map = new FilesystemMap();
        $map->remove('foo');
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @dataProvider getInvalidDomains
     */
    public function shouldNotAllowToSetAnInvalidDomain($name)
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
