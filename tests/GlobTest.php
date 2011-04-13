<?php

namespace Gaufrette;

class GlobTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestGetRegexData
     */
    public function testGetRegex($pattern, $expected)
    {
        $glob = new Glob($pattern);

        $this->assertEquals($expected, $glob->getRegex());
    }

    public function getTestGetRegexData()
    {
        return array(
            array(
                '*.php',
                '#^.*\.php$#'
            ),
            array(
                '*.{php,twig}',
                '#^.*\.(php|twig)$#'
            )
        );
    }

    /**
     * @dataProvider getTestMatchesData
     */
    public function testMatches($pattern, $filename, $expected)
    {
        $glob = new Glob($pattern);

        $this->assertEquals($expected, $glob->matches($filename), $pattern . ' -> ' . $glob->getRegex());
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

    /**
     * @dataProvider getTestFilterData
     */
    public function testFilter($pattern, $input, $expected)
    {
        $glob = new Glob($pattern);

        $this->assertEquals(array_values($expected), array_values($glob->filter($input)));
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
                )
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
                )
            ),
            array(
                '*.txt',
                new \ArrayObject(array('foo.txt', 'bar.png')),
                array('foo.txt')
            )
        );
    }
}
