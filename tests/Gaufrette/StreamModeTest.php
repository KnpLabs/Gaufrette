<?php

namespace Gaufrette;

class StreamModeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getDataToTestAllowsRead
     */
    public function testAllowsRead($mode, $expected)
    {
        $streamMode = new StreamMode($mode);

        if ($expected) {
            $this->assertTrue($streamMode->allowsRead(), sprintf('The mode "%s" should allow read', $mode));
        } else {
            $this->assertFalse($streamMode->allowsRead(), sprintf('The mode "%s" should NOT allow read', $mode));
        }
    }

    /**
     * @dataProvider getDataToTestAllowsWrite
     */
    public function testAllowsWrite($mode, $expected)
    {
        $streamMode = new StreamMode($mode);

        if ($expected) {
            $this->assertTrue($streamMode->allowsWrite(), sprintf('The mode "%s" should allow write', $mode));
        } else {
            $this->assertFalse($streamMode->allowsWrite(), sprintf('The mode "%s" should NOT allow write', $mode));
        }
    }

    /**
     * @dataProvider getDataToTestAllowsNewFileOpening
     */
    public function testAllowsNewFileOpening($mode, $expected)
    {
        $streamMode = new StreamMode($mode);

        if ($expected) {
            $this->assertTrue($streamMode->allowsNewFileOpening(), sprintf('The mode "%s" should allow to open a new file', $mode));
        } else {
            $this->assertFalse($streamMode->allowsNewFileOpening(), sprintf('The mode "%s" should NOT allow to open a new file', $mode));
        }
    }

    /**
     * @dataProvider getDataToTestAllowsExistingFileOpening
     */
    public function testAllowsExistingFileOpening($mode, $expected)
    {
        $streamMode = new StreamMode($mode);

        if ($expected) {
            $this->assertTrue($streamMode->allowsExistingFileOpening(), sprintf('The mode "%s" should allow to open an existing file', $mode));
        } else {
            $this->assertFalse($streamMode->allowsExistingFileOpening(), sprintf('The mode "%s" should NOT allow to open an existing file', $mode));
        }
    }

    /**
     * @dataProvider getDataToTestImpliesExistingContentDeletion
     */
    public function testImpliesExistingContentDeletion($mode, $expected)
    {
        $streamMode = new StreamMode($mode);

        if ($expected) {
            $this->assertTrue($streamMode->impliesExistingContentDeletion(), sprintf('The mode "%s" should imply the existing content deletion', $mode));
        } else {
            $this->assertFalse($streamMode->impliesExistingContentDeletion(), sprintf('The mode "%s" should NOT imply the existing content deletion', $mode));
        }
    }

    /**
     * @dataProvider getDataToTestImpliesPositioningCursorAtTheBeginning
     */
    public function testImpliesPositioningCursorAtTheBeginning($mode, $expected)
    {
        $streamMode = new StreamMode($mode);

        if ($expected) {
            $this->assertTrue($streamMode->impliesPositioningCursorAtTheBeginning(), sprintf('The mode "%s" should imply positioning the cursor at the beginning', $mode));
        } else {
            $this->assertFalse($streamMode->impliesPositioningCursorAtTheBeginning(), sprintf('The mode "%s" should NOT imply positioning the cursor at the beginning', $mode));
        }
    }

    /**
     * @dataProvider getDataToTestImpliesPositioningCursorAtTheEnd
     */
    public function testImpliesPositioningCursorAtTheEnd($mode, $expected)
    {
        $streamMode = new StreamMode($mode);

        if ($expected) {
            $this->assertTrue($streamMode->impliesPositioningCursorAtTheEnd(), sprintf('The mode "%s" should imply positioning the cursor at the end', $mode));
        } else {
            $this->assertFalse($streamMode->impliesPositioningCursorAtTheEnd(), sprintf('The mode "%s" should NOT imply positioning the cursor at the end', $mode));
        }
    }

    /**
     * @dataProvider getDataToTestIsBinary
     */
    public function testIsBinary($mode, $expected)
    {
        $streamMode = new StreamMode($mode);

        if ($expected) {
            $this->assertTrue($streamMode->isBinary(), sprintf('The mode "%s" should be binary', $mode));
        } else {
            $this->assertFalse($streamMode->isBinary(), sprintf('The mode "%s" should NOT be binary', $mode));
        }
    }

    /**
     * @dataProvider getDataToTestIsText
     */
    public function testIsText($mode, $expected)
    {
        $streamMode = new StreamMode($mode);

        if ($expected) {
            $this->assertTrue($streamMode->isText(), sprintf('The mode "%s" should be text', $mode));
        } else {
            $this->assertFalse($streamMode->isText(), sprintf('The mode "%s" should NOT be text', $mode));
        }
    }

    public function getDataToTestAllowsRead()
    {
        return $this->getDataTuples(array('mode', 'read'));
    }

    public function getDataToTestAllowsWrite()
    {
        return $this->getDataTuples(array('mode', 'write'));
    }

    public function getDataToTestAllowsExistingFileOpening()
    {
        return $this->getDataTuples(array('mode', 'edit'));
    }

    public function getDataToTestAllowsNewFileOpening()
    {
        return $this->getDataTuples(array('mode', 'create'));
    }

    public function getDataToTestImpliesExistingContentDeletion()
    {
        return $this->getDataTuples(array('mode', 'clear'));
    }

    public function getDataToTestImpliesPositioningCursorAtTheBeginning()
    {
        $tuples = $this->getDataTuples(array('mode', 'position'));

        array_walk($tuples, function (&$tuple) { $tuple[1] = 'beginning' === $tuple[1]; });

        return $tuples;
    }

    public function getDataToTestImpliesPositioningCursorAtTheEnd()
    {
        $tuples = $this->getDataToTestImpliesPositioningCursorAtTheBeginning();

        array_walk($tuples, function (&$tuple) { $tuple[1] = !$tuple[1]; });

        return $tuples;
    }

    public function getDataToTestIsBinary()
    {
        return $this->getDataTuples(array('mode', 'binary'));
    }

    public function getDataToTestIsText()
    {
        $tuples = $this->getDataToTestIsBinary();

        array_walk($tuples, function (&$tuple) { $tuple[1] = !$tuple[1]; });

        return $tuples;
    }

    public function getDataTuples(array $keys)
    {
        return array_map(
            function ($hash) use($keys) {
                $tuple = array();
                foreach ($keys as $key) {
                    $tuple[] = $hash[$key];
                }

                return $tuple;
            },
            $this->getDataHashes()
        );
    }

    private function getDataHashes()
    {
        return array(
            array(
                'mode'      => 'r',
                'read'      => true,
                'write'     => false,
                'edit'      => true,
                'create'    => false,
                'clear'     => false,
                'binary'    => false,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'rb',
                'read'      => true,
                'write'     => false,
                'edit'      => true,
                'create'    => false,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'r+',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => false,
                'clear'     => false,
                'binary'    => false,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'r+b',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => false,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'rb+',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => false,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'w',
                'read'      => false,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => true,
                'binary'    => false,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'wb',
                'read'      => false,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => true,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'w+',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => true,
                'binary'    => false,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'w+b',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => true,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'wb+',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => true,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'a',
                'read'      => false,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => false,
                'position'  => 'end',
            ),
            array(
                'mode'      => 'a+',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => false,
                'position'  => 'end',
            ),
            array(
                'mode'      => 'ab',
                'read'      => false,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'end',
            ),
            array(
                'mode'      => 'a+b',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'end',
            ),
            array(
                'mode'      => 'ab+',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'end',
            ),
            array(
                'mode'      => 'ab+',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'end',
            ),
            array(
                'mode'      => 'x',
                'read'      => false,
                'write'     => true,
                'edit'      => false,
                'create'    => true,
                'clear'     => false,
                'binary'    => false,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'xb',
                'read'      => false,
                'write'     => true,
                'edit'      => false,
                'create'    => true,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'x+',
                'read'      => true,
                'write'     => true,
                'edit'      => false,
                'create'    => true,
                'clear'     => false,
                'binary'    => false,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'x+b',
                'read'      => true,
                'write'     => true,
                'edit'      => false,
                'create'    => true,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'xb+',
                'read'      => true,
                'write'     => true,
                'edit'      => false,
                'create'    => true,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'c',
                'read'      => false,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => false,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'cb',
                'read'      => false,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'c+',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => false,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'c+b',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'beginning',
            ),
            array(
                'mode'      => 'cb+',
                'read'      => true,
                'write'     => true,
                'edit'      => true,
                'create'    => true,
                'clear'     => false,
                'binary'    => true,
                'position'  => 'beginning',
            ),
        );
    }
}
