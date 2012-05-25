<?php

namespace Gaufrette\Util;

class SizeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getFromContentData
     */
    public function testFromContent($content, $expected, $message)
    {
        $this->assertEquals($expected, Size::fromContent($content), $message);
    }

    public function getFromContentData()
    {
        return array(
            array(
                'Some content',
                12,
                'an ASCII string'
            ),
            array(
                'æ«€¶ŧ←↓→øþßðđŋħjĸł»¢“”n',
                50,
                'a string with special chars'
            ),
        );
    }
}
