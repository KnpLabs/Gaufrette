<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;

class MogileFSTest extends \PHPUnit_Framework_TestCase
{
    private $testHost = array('127.0.0.1:7001');
    private $testDomain = 'test';
    private $testClass = 'files';

    private $testFileKey = 'myfile';
    private $testFileContent = 'Lorem Ipsum...';

    public function setUp()
    {
        $mogile = new MogileFS($this->testDomain, $this->testHost);

        if (!$mogile->connect()) {
            $this->markTestSkipped('Cannot connect to server.');
        }
    }

    public function testGetDomains()
    {
        $mogile = new MogileFS($this->testDomain, $this->testHost);
        $domains = $mogile->getDomains();

        if (!is_array($domains) || count($domains) == 0) {
            $this->markTestSkipped('Cannot find any domains from server');
        }

        return $domains;
    }

    /**
     * @depends testGetDomains
     */
    public function testValidDomain(array $domains)
    {
        $domain_found = false;
        $class_found = false;

        foreach ($domains as $row)
        {
            if ($row['name'] == $this->testDomain) {
                $domain_found = true;

                foreach ($row['classes'] as $key => $val) {
                    if ($key == $this->testClass) {
                        $class_found = true;
                    }
                }

                break;
            }
        }

        $this->assertTrue($domain_found, 'Cannot find "' . $this->testDomain . '" domain from server');
        $this->assertTrue($class_found, 'Cannot find "' . $this->testClass . '" class from server');
    }

    public function testWriteReadDelete()
    {
        $mogile = new MogileFS($this->testDomain, $this->testHost);

        $this->assertGreaterThan(0, $mogile->write($this->testFileKey, $this->testFileContent, array('mogile_class' => $this->testClass)));
        $this->assertTrue($mogile->exists($this->testFileKey));

        $this->assertEquals($mogile->read($this->testFileKey), $this->testFileContent);

        $this->assertTrue($mogile->delete($this->testFileKey));
        $this->assertFalse($mogile->exists($this->testFileKey));
    }

    public function testRename()
    {
        $newTestFileKey = $this->testFileKey . '_123';

        $mogile = new MogileFS($this->testDomain, $this->testHost);

        $this->assertGreaterThan(0, $mogile->write($this->testFileKey, $this->testFileContent, array('mogile_class' => $this->testClass)));
        $this->assertTrue($mogile->exists($this->testFileKey));

        $this->assertTrue($mogile->rename($this->testFileKey, $newTestFileKey));
        $this->assertTrue($mogile->exists($newTestFileKey));
        $this->assertEquals($mogile->read($newTestFileKey), $this->testFileContent);

        $this->assertFalse($mogile->exists($this->testFileKey));
        $this->assertTrue($mogile->delete($newTestFileKey));
    }

}