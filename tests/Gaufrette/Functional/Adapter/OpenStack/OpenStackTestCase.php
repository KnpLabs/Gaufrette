<?php

namespace Gaufrette\Functional\Adapter\OpenStack;

use Gaufrette\Functional\Adapter\FunctionalTestCase;

abstract class OpenStackTestCase extends FunctionalTestCase
{
    /** @var \OpenStack\ObjectStore\v1\Service */
    protected $objectStore;

    /** @var string */
    protected $container;

    public function tearDown()
    {
        if ($this->filesystem === null) {
            return;
        }

        // container must be empty to be deleted
        array_map(function ($key) {
            $this->filesystem->delete($key);
        }, $this->filesystem->keys());

        $this->objectStore->getContainer($this->container)->delete();
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetChecksum()
    {
        $this->filesystem->write('foo', 'Some content');

        $this->assertEquals(md5('Some content'), $this->filesystem->checksum('foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetSize()
    {
        $this->filesystem->write('foo', 'Some content');

        $this->assertEquals(strlen('Some content'), $this->filesystem->size('foo'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldGetMimeType()
    {
        $this->filesystem->write('foo.txt', 'Some content');

        $this->assertEquals('text/plain', $this->filesystem->mimeType('foo.txt'));
    }

    /**
     * @test
     * @group functional
     */
    public function shouldSetAndGetMetadata()
    {
        $this->filesystem->write('test.txt', 'Some content');
        $this->filesystem->getAdapter()->setMetadata('test.txt', [
            'Some-Meta' => 'foo',
            'Custom-Stuff' => 'bar',
        ]);

        $this->assertEquals([
            'Some-Meta' => 'foo',
            'Custom-Stuff' => 'bar',
        ], $this->filesystem->getAdapter()->getMetadata('test.txt'));
    }
}
