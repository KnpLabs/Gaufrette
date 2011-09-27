<?php

namespace Gaufrette\FileCursor;

use Gaufrette\Filesystem;
use Gaufrette\FileCursor;
use Gaufrette\File;

/**
 * Helper class for looping files efficiently without assoc arrays
 * 
 * This should be in a separate file but that would require refactoring whole Adapter folder
 */
class GridFS extends FileCursor
{
	public function __construct(\Iterator $parentCursor, Filesystem $filesystem)
	{
		parent::__construct($parentCursor, $filesystem);
	}
	
	/**
	* {@InheritDoc}
	*/
	public function current()
	{
		$r = $this->parentCursor->current();

		$key = $r->file['key'];
		$file = new File($key, $this->filesystem);
		$file->setMetadata($r->file['metadata']);
		$file->setName($r->file['filename']);
		//$file->mimetype = $r['mimetype'];
		//$file->setUploadDate($r['uploadDate']);
		return $file;
	}
}
