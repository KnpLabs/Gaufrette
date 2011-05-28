<?php

namespace Gaufrette;

class ChecksumTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getFromContentData
     */
    public function testFromContent($content, $expected)
    {
        $this->assertEquals($expected, Checksum::fromContent($content));
    }

    public function getFromContentData()
    {
        return array(
            array(
                '',
                'd41d8cd98f00b204e9800998ecf8427e'
            ),
            array(
                'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
                'fa5c89f3c88b81bfd5e821b0316569af'
            )
        );
    }

    /**
     * @dataProvider getMatchesContentData
     */
    public function testMatchesContent($checksum, $content, $expected)
    {
        $this->assertEquals($expected, Checksum::matchesContent($checksum, $content));
    }

    public function getMatchesContentData()
    {
        return array(
            array(
                'd41d8cd98f00b204e9800998ecf8427e',
                '',
                true
            ),
            array(
                'd41d8cd98f00b204e9800998ecf8427e',
                'foo',
                false
            ),
            array(
                'fa5c89f3c88b81bfd5e821b0316569af',
                'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
                true
            ),
            array(
                'fa5c89f3c88b81bfd5e821b0316569af',
                'Lorem ipsum dolor sit amet.',
                false
            )
        );
    }
}
