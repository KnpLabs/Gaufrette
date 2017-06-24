<?php

namespace Gaufrette\Unit\Util;

use Gaufrette\Util\Path;

/**
 * Path test.
 *
 * @coversDefaultClass Gaufrette\Util\Path
 */
class PathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @covers ::parseUrl
     */
    public function shouldParseUrlCorrectly()
    {
        $url = 'gaufrette://föô/bàr?bäz=1#qùx';
        $expected = [
            'scheme' => 'gaufrette',
            'host' => 'föô',
            'path' => '/bàr',
            'query' => 'bäz=1',
            'fragment' => 'qùx',
        ];
        $parts = Path::parseUrl($url);

        $this->assertEquals($expected, $parts);
    }

    /**
     * @test
     * @covers ::parseUrl
     */
    public function shouldReturnFalseWhenParsingMalformedUrl()
    {
        $url = ':';
        $expected = false;
        $parts = Path::parseUrl($url);

        $this->assertEquals($expected, $parts);
    }
}
