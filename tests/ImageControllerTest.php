<?php

use Mockery as m;

class ImageControllerTest extends TestCase {

  public function createApplication()
  {
    $app = parent::createApplication();
    return $app;
  }

  public function setUp()
  {
    parent::setUp();
    include __DIR__.'/../src/routes.php';
    $this->mock = $this->mock('MOK\ImageUpload\Repositories\LocalRepositoryInterface');
  }

  public function mock($class)
  {
    $mock = m::mock($class);
    $this->app->instance($class, $mock);
    return $mock;
  }

  public function testFormOK()
  {
//    $response = $this->action('GET', 'MOK\ImageUpload\ImageController@form', array(
//      'owner' => 'User',
//    ));
//    $this->assertResponseOk();
  }

  public function testFormJSON()
  {
//    $response = $this->action('GET', 'MOK\ImageUpload\ImageController@form', array(
//      'owner' => 'User',
//    ));
//    $this->assertNotEquals(false, json_decode($response->getContent()));
  }



}
