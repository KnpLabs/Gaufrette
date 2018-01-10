<?php
/**
 * Functional tests for the GoogleCloudClientStorage adapter.
 * Edit the phpunit.xml.dist Google Cloud Client adapter section for configuration
 * @author  Lech Buszczynski <lecho@phatcat.eu>
 */

namespace Gaufrette\Functional\Adapter;

class GoogleCloudClientStorageTest extends FunctionalTestCase
{   
    private $string     = 'Yeah mate. No worries, I uploaded just fine. Meow!';
    private $directory  = 'tests';
    
    public function setUp()
    {
        $gccs_project_id         = getenv('GCCS_PROJECT_ID');
        $gccs_bucket_name        = getenv('GCCS_BUCKET_NAME');
        $gccs_json_key_file_path = getenv('GCCS_JSON_KEY_FILE_PATH');

        if (empty($gccs_project_id) || empty($gccs_bucket_name) || empty($gccs_json_key_file_path))
        {
            $this->markTestSkipped('Required enviroment variables are not defined.');
        } elseif (!is_readable($gccs_json_key_file_path)) {
            $this->markTestSkipped(sprintf('Cannot read JSON key file from "%s".', $gccs_json_key_file_path));
        }
        
        $storage = new \Google\Cloud\Storage\StorageClient(
            array(
                'projectId'     => $gccs_project_id,
                'keyFilePath'   => $gccs_json_key_file_path
            )
        );

        $adapter = new \Gaufrette\Adapter\GoogleCloudClientStorage($storage, $gccs_bucket_name,
            array(
                'directory' => $this->directory,
                'acl'       => array(
                    'allUsers' => \Google\Cloud\Storage\Acl::ROLE_READER
                )
            )
        );

        $this->filesystem = new \Gaufrette\Filesystem($adapter);
    }
    
    /**
     * @test
     * @group functional
     * @group gccs
     * 
     * @expectedException \RuntimeException
     */
    public function shouldFailIfBucketIsNotAccessible()
    {
        /** @var \Gaufrette\Adapter\GoogleCloudClientStorage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $adapter->setBucket('meow_'.mt_rand());
    }
    
    /**
     * @test
     * @group functional
     * @group gccs
     */
    public function shouldListBucketContent()
    {
        $this->assertEquals(strlen($this->string), $this->filesystem->write('Phat/Cat.txt', $this->string, true));
        $keys = $this->filesystem->keys();
        $file = $this->directory ? $this->directory.'/Phat/Cat.txt' : 'Phat/Cat.txt';
        $this->assertTrue(in_array($file, $keys));
        $this->filesystem->delete('Phat/Cat.txt');
    }
    
    /**
     * @test
     * @group functional
     * @group gccs
     */
    public function shouldWriteAndReadFile()
    {
        $this->assertEquals(strlen($this->string), $this->filesystem->write('Phat/Cat.txt', $this->string, true));
        $this->assertEquals(strlen($this->string), $this->filesystem->write('Phatter/Cat.txt', $this->string, true));

        $this->assertEquals($this->string, $this->filesystem->read('Phat/Cat.txt'));
        $this->assertEquals($this->string, $this->filesystem->read('Phatter/Cat.txt'));

        $this->filesystem->delete('Phat/Cat.txt');
        $this->filesystem->delete('Phatter/Cat.txt');
    }
    
    /**
     * @test
     * @group functional
     * @group gccs
     */
    public function shouldWriteAndReadFileMetadata()
    {
        /** @var \Gaufrette\Adapter\GoogleCloudClientStorage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $file   = 'PhatCat/Cat.txt';       
        $adapter->setMetadata($file, array('OhMy' => 'I am a cat file!'));
        $this->assertEquals(strlen($this->string), $this->filesystem->write($file, $this->string, true));
        $info = $adapter->getMetadata($file);
        $this->assertEquals($info['OhMy'], 'I am a cat file!');
        $this->filesystem->delete($file);
    }
    
    /**
     * @test
     * @group functional
     * @group gccs
     */
    public function shouldWriteAndRenameFile()
    {
        /** @var \Gaufrette\Adapter\GoogleCloudClientStorage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $file   = 'Cat.txt';       
        $adapter->setMetadata($file, array('OhMy' => 'I am a cat file!'));
        $this->assertEquals(strlen($this->string), $this->filesystem->write($file, $this->string, true));
        $adapter->rename('Cat.txt', 'Kitten.txt');      
        $this->assertEquals($adapter->getMetadata('Kitten.txt'), $adapter->getResourceByName('Kitten.txt', 'metadata'));
        $this->filesystem->delete('Kitten.txt');
    }
    
    /**
     * @test
     * @group functional
     * @group gccs
     */
    public function shouldWriteAndReadPublicFile()
    {
        /** @var \Gaufrette\Adapter\GoogleCloudClientStorage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $file   = 'Cat.txt';       
        $this->assertEquals(strlen($this->string), $this->filesystem->write($file, $this->string, true));

        if ($this->directory)
        {
            $public_link = sprintf('https://storage.googleapis.com/%s/%s/Cat.txt', $adapter->getBucket()->name(), $this->directory);
        } else {
            $public_link = sprintf('https://storage.googleapis.com/%s/Cat.txt', $adapter->getBucket()->name());
        }

        $headers = @get_headers($public_link);       
        $this->assertEquals($headers[0], 'HTTP/1.0 200 OK');       
        $this->filesystem->delete('Cat.txt');
    }    
}