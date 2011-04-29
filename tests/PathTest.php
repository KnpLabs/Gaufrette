<?php

namespace Gaufrette;

class PathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getNormalizeData
     */
    public function testNormalize($path, $expected)
    {
        $this->assertEquals($expected, Path::normalize($path));
    }

    public function getNormalizeData()
    {
        return array(
            array('/foo/bar', '/foo/bar'),
            array('/foo/bar/', '/foo/bar'),
            array('/foo/foo/../bar', '/foo/bar'),
            array('/foo/./bar', '/foo/bar'),
            array('/foo/..', '/'),
            array('/foo/../..', '/'),
            array('foo/bar', 'foo/bar'),
            array('foo/bar/', 'foo/bar'),
            array('foo/foo/../bar', 'foo/bar'),
            array('foo/./bar', 'foo/bar'),
            array('foo/..', ''),
            array('foo/../..', '..'),
            array('C:\foo\bar', 'c:/foo/bar'),
            array('C:\..\..', 'c:/')
        );
    }

    /**
     * @dataProvider getIsAbsoluteData
     */
    public function testIsAbsolute($path, $expected)
    {
        return $this->assertEquals($expected, Path::isAbsolute($path));
    }

    public function getIsAbsoluteData()
    {
        return array(
            array('/foo/bar', true),
            array('foo/bar', false),
            array('C:/foo/bar', true),
            array('c:/foo/bar', true),
            array('_:/foo/bar', false),
            array('abc:/foo/bar', false)
        );
    }

    /**
     * @dataProvider getGetAbsolutePrefixData
     */
    public function testGetAbsolutePrefix($path, $expected)
    {
        return $this->assertEquals($expected, Path::getAbsolutePrefix($path));
    }

    public function getGetAbsolutePrefixData()
    {
        return array(
            array('/foo/bar', '/'),
            array('foo/bar', false),
            array('C:/foo/bar', 'c:/'),
            array('c:/foo/bar', 'c:/'),
            array('_:/foo/bar', false),
            array('abc:/foo/bar', false)
        );
    }
}
