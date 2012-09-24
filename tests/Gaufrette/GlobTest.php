<?php

namespace Gaufrette;

class GlobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers Gaufrette\Glob
     */
    public function shouldGetPatternSetInConstructor()
    {
        $glob = new Glob('pattern');

        $this->assertEquals('pattern', $glob->getPattern());
    }

    /**
     * @test
     * @dataProvider getTestMatchesData
     * @covers Gaufrette\Glob
     */
    public function shouldFindMatchingFilename($pattern, $filename, $expected)
    {
        $glob = new Glob($pattern, false, false);

        $this->assertEquals($expected, $glob->matches($filename), $pattern . ' -> ' . $glob->getRegex());
    }

    /**
     * @test
     * @dataProvider getTestFilterData
     * @covers Gaufrette\Glob
     */
    public function shouldFilterData($pattern, $input, $expected, $strictLeadingDot = true, $strictWildcartSlash = true)
    {
        $glob = new Glob($pattern, $strictLeadingDot, $strictWildcartSlash);

        $this->assertEquals(array_values($expected), array_values($glob->filter($input)));
    }

    /**
     * @test
     * @covers Gaufrette\Glob
     */
    public function shouldStrictLeadingDot()
    {
        $glob = new Glob('*pattern');

        $this->assertNotRegExp($glob->getRegex(), '.pattern');
        $this->assertRegExp($glob->getRegex(), 'pattern');
    }

    /**
     * @test
     * @covers Gaufrette\Glob
     */
    public function shouldStrictWildcartSlash()
    {
        $glob = new Glob('pattern*');

        $this->assertRegExp($glob->getRegex(), 'patternaaa');
        $this->assertNotRegExp($glob->getRegex(), 'pattern/aaa');

        $glob = new Glob('pattern*', true, false);

        $this->assertRegExp($glob->getRegex(), 'patternaaa');
        $this->assertRegExp($glob->getRegex(), 'pattern/aaa');
    }

    public function getTestMatchesData()
    {
        return array(
            array('*.txt', 'foobar', false),
            array('*.txt', 'foo.txt', true),
            array('*.txt', '.txt', true),
            array('??', 'a', false),
            array('??', 'ab', true),
            array('??', 'abc', false),
            array('??', '.a', true),
            array('??*', 'a', false),
            array('??*', 'ab', true),
            array('??*', 'abc', true),
            array('??*', '.a', true),
            array('??*', 'a.b', true),
            array('g?*', 'a', false),
            array('g?*', 'g', false),
            array('g?*', 'ab', false),
            array('g?*', 'ga', true),
            array('*.{jpg,gif,png}', 'foo.txt', false),
            array('*.{jpg,gif,png}', 'foo.jpg', true),
            array('*.{jpg,gif,png}', 'foo.gif', true),
            array('*.{jpg,gif,png}', 'foo.png', true),
            array('*.{jpg,gif,png}', 'foo.bar.png', true),
            array('*.{jpg,gif,png}', '.png', true),
            array('DN?????.dat', 'foo.txt', false),
            array('DN?????.dat', 'DNABCDEF.dat', false),
            array('DN?????.dat', 'DNABCDE.dat', true),
            array('DN?????.dat', 'DNABCD.dat', false),
            array('DN[0-9][0-9][0-9][0-9][0-9].dat', 'foo.txt', false),
            array('DN[0-9][0-9][0-9][0-9][0-9].dat', 'DNABCDE.dat', false),
            array('DN[0-9][0-9][0-9][0-9][0-9].dat', 'DN01234.dat', true),
            array('DN[0-9][0-9][0-9][0-9][0-9].dat', 'DN012345.dat', false),
            array('*\[[0-9]\].*', 'abcdef', false),
            array('*\[[0-9]\].*', 'foo[A].txt', false),
            array('*\[[0-9]\].*', 'foo[A].txt', false),
            array('*\[[0-9]\].*', 'foo[1].txt', true),
            array('*\[[0-9]\].*', 'foo[1]', false),
            array('*\[[0-9]\].*', '[1].txt', true),
            array('subdir/img*/th_?*', 'subdir/css/th_a', false),
            array('subdir/img*/th_?*', 'subdir/img/th_a', true),
            array('subdir/img*/th_?*', 'subdir/imgs/th_ab', true),
            array('subdir/img*/th_?*', 'subdir/img/th_', false)
        );
    }

    public function getTestFilterData()
    {
        return array(
            array(
                '*.php',
                array(
                    '.htaccess',
                    'index.php',
                    'app/bootstrap.php',
                    'app/config/config.yml'
                ),
                array(
                    'index.php',
                    'app/bootstrap.php'
                ),
                true,
                false
            ),
            array(
                '*.php',
                array(
                    '.htaccess',
                    'index.php',
                    'app/bootstrap.php',
                    'app/config/config.yml'
                ),
                array(
                    'index.php',
                ),
            ),
            array(
                'web/images/*.{png,jpg}',
                array(
                    'data/sample.png',
                    'web/images/frog.png',
                    'web/images/foo.gif',
                    'web/images/foo/bar.jpg'
                ),
                array(
                    'web/images/frog.png',
                    'web/images/foo/bar.jpg'
                ),
                true,
                false
            ),
            array(
                '*.txt',
                new \ArrayObject(array('foo.txt', 'bar.png')),
                array('foo.txt')
            )
        );
    }
}
