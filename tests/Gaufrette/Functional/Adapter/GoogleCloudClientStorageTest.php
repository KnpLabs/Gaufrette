<?php

namespace Gaufrette\Functional\Adapter;

/**
 * Functional tests for the GoogleCloudClientStorage adapter.
 *
 * Copy the ../adapters/GoogleCloudClientStorage.php.dist to GoogleCloudClientStorage.php and
 * adapt to your needs.
 *
 * @author  Lech Buszczynski <lecho@phatcat.eu>
 */
class GoogleCloudClientStorageTest extends FunctionalTestCase
{
    private $string = 'Yeah mate. No worries, I uploaded just fine. Meow!';
    
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
        $options = $adapter->getOptions();
        $adapter->setBucket('meow_'.mt_rand());
        $adapter->setOptions($options);
    }
    
    /**
     * @test
     * @group functional
     * @group gccs
     */
    public function shouldListBucketContent()
    {
        /** @var \Gaufrette\Adapter\GoogleCloudClientStorage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $options = $adapter->getOptions();        

        $this->assertEquals(strlen($this->string), $this->filesystem->write('Phat/Cat.txt', $this->string, true));
        $keys = $this->filesystem->keys();               
        $this->assertEquals($keys[0], 'Phat/Cat.txt');
        $this->filesystem->delete('Phat/Cat.txt');        
        $adapter->setOptions($options);
    }
    
    /**
     * @test
     * @group functional
     * @group gccs
     */
    public function shouldWriteAndReadFile()
    {
        /** @var \Gaufrette\Adapter\GoogleCloudClientStorage $adapter */
        $adapter = $this->filesystem->getAdapter();
        $options = $adapter->getOptions();
        //$adapter->setOptions(array('directory' => 'Phat'));
        $this->assertEquals(strlen($this->string), $this->filesystem->write('Phat/Cat.txt', $this->string, true));
        $this->assertEquals(strlen($this->string), $this->filesystem->write('Phatter/Cat.txt', $this->string, true));

        $this->assertEquals($this->string, $this->filesystem->read('Phat/Cat.txt'));
        $this->assertEquals($this->string, $this->filesystem->read('Phatter/Cat.txt'));

        $this->filesystem->delete('Phat/Cat.txt');
        $this->filesystem->delete('Phatter/Cat.txt');
        $adapter->setOptions($options);
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
        $options = $adapter->getOptions();
        $file   = 'PhatCat/Cat.txt';       
        $adapter->setMetadata($file, array('OhMy' => 'I am a cat file!'));
        $this->assertEquals(strlen($this->string), $this->filesystem->write($file, $this->string, true));
        $info = $adapter->getMetadata($file);
        $this->assertEquals($info['OhMy'], 'I am a cat file!');
        $this->filesystem->delete($file);
        $adapter->setOptions($options);
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
        $options = $adapter->getOptions();
        $file   = 'Cat.txt';       
        $adapter->setMetadata($file, array('OhMy' => 'I am a cat file!'));
        $this->assertEquals(strlen($this->string), $this->filesystem->write($file, $this->string, true));
        $adapter->rename('Cat.txt', 'Kitten.txt');      
        $this->assertEquals($adapter->getMetadata('Kitten.txt'), $adapter->getResourceByName('Kitten.txt', 'metadata'));
        $this->filesystem->delete('Kitten.txt');
        $adapter->setOptions($options);
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
        $options = $adapter->getOptions();
        $file   = 'Cat.txt';       
        $this->assertEquals(strlen($this->string), $this->filesystem->write($file, $this->string, true));

        $public_link = sprintf('https://storage.googleapis.com/%s/Cat.txt', $adapter->getBucket()->name());
        
        $headers = @get_headers($public_link);       
        $this->assertEquals($headers[0], 'HTTP/1.0 200 OK');       
        $this->filesystem->delete('Cat.txt');
        $adapter->setOptions($options);
    }
    
}