<?php

use MOK\ImageUpload\Repositories\RackspaceRepository;

class RackspaceRepositoryTest extends TestCase {


  protected $repo;
  protected $useAPI = false;

  public function setUp()
  {
    parent::setUp();
    // Get the RackspaceRepository class
    $this->repo = new RackspaceRepository($this->app);
  }

  /**
   * Test instantiation of the Rackspace API class
   */
  public function testSetupApi()
  {
    if ($this->useAPI) {
      $api = $this->repo->api();
      $this->assertInstanceOf('OpenCloud\Rackspace', $api, 'Rackspace API not instantiated.');
    }
  }

  /**
   * Get an instance of the Swift Object Store for cloud files
   */
  public function testSwift()
  {
    if ($this->useAPI) {
      $swift = $this->repo->swift();
      $this->assertInstanceOf('OpenCloud\ObjectStore\Service', $swift, 'Swift ObjectStore not loaded.');
    }
  }

  /**
   * Get the 'laravel_image_uploader' container
   */
  public function testGetContainer()
  {
    if ($this->useAPI) {
      $container = $this->repo->getContainer('laravel_image_uploader');

      $this->assertInstanceOf('OpenCloud\ObjectStore\Resource\Container', $container, 'OpenCloud container object not found.');
      $this->assertEquals('laravel_image_uploader', $container->name, 'Container name does not match.');
    }
  }

  public function testListObjects()
  {
    if ($this->useAPI) {
      $objects = $this->repo->listObjects();

      $this->assertTrue(is_array($objects), 'Object list should return array.');
    }
  }

  public function testListObjectsWithPrefix()
  {
    if ($this->useAPI) {
      $objects = $this->repo->listObjects('Jel');

      $this->assertTrue(is_array($objects), 'Object list should return array.');
      $this->assertEquals(1, count($objects), 'Object list should return one object.');
    }
  }

  public function testHasObject()
  {
    if ($this->useAPI) {
      $actual = $this->repo->hasObject('1-400x300.jpg');
      $this->assertTrue($actual, 'Container does not have expected object');
    }
  }

  public function testGetObject()
  {
    if ($this->useAPI) {
      $containerUrl = 'https://storage101.lon3.clouddrive.com/v1/MossoCloudFS_a82166b0-a8ff-4f36-bdcf-310f296a9479/laravel_image_uploader/';

      $object = $this->repo->getObject('1.jpg');

      $this->assertInstanceOf('OpenCloud\ObjectStore\Resource\DataObject', $object, 'OpenCloud object not found.');
      $this->assertEquals('1.jpg', $object->name, 'Object name does not match.');
      $this->assertEquals($containerUrl . '1.jpg', $object->PublicUrl(), 'Object URL does not match.');
    }
  }

  public function testSaveObject()
  {
    if ($this->useAPI) {
      $data = array(
        'content_type' => 'image/jpeg',
        'path'         => public_path('img/content/user/1.jpg'),
      );

      $object = $this->repo->saveObject('1.jpg', $data);

      $this->assertInstanceOf('OpenCloud\ObjectStore\Resource\DataObject', $object, 'OpenCloud object not saved.');
    }
  }

  public function testDeleteObject()
  {
    if ($this->useAPI) {
      $this->repo->deleteObject('1.jpg');
      $object = $this->repo->getObject('1.jpg');

      $this->assertFalse($object);
    }
  }
}
