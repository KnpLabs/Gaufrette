<?php

namespace Gaufrette\Adapter;

use Gaufrette\Adapter;
use Gaufrette\Filesystem;

class GridFSTest extends FunctionalTestCase
{
    public function setUp()
    {
        if (!class_exists('\Mongo')) {
            return $this->markTestSkipped('Mongo class not found.');
        }

        $mongo = new \Mongo($_SERVER['MONGO_SERVER']);

        if (!$mongo->connected) {
            $this->markTestSkipped(sprintf(
                'Cannot connect to Mongo server (%s).',
                $_SERVER['MONGO_SERVER']
            ));
        }

        $db = $mongo->selectDB($_SERVER['MONGO_DATABASE']);

        $grid = $db->getGridFS();
        $grid->remove();

        $this->adapter = new GridFS($grid);
    }

    public function tearDown()
    {
        if (null === $this->adapter) {
            return;
        }

        $this->adapter = null;
    }
}
