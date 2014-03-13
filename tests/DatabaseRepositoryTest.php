<?php

use MOK\ImageUpload\Models\Image as Image;

class DatabaseRepositoryTest extends TestCase {

  protected $repo;

  public function __construct()
  {
    // Get the DatabaseRepository class
    $this->repo = new MOK\ImageUpload\Repositories\DatabaseRepository;
  }

  public function setUp()
  {
    parent::setUp();
    // Add the database tables to memory
    Artisan::call('migrate');
    Artisan::call('migrate', array('--bench' => 'markokeeffe/image-upload'));
  }

  public function testAdd()
  {
    $data = array(
      'imageable_type' => 'User',
    );
    $model = $this->repo->add($data);
    $this->assertInstanceOf('MOK\ImageUpload\Models\Image', $model);
  }

  public function testGet()
  {
    $image = new Image(array(
      'imageable_type' => 'User',
    ));
    $image->save();

    $model = $this->repo->get($image->id);
    $this->assertEquals($image->id, $model->id);
  }

  public function testUpdate()
  {
    $image = new Image(array(
      'imageable_type' => 'User',
    ));
    $image->save();

    $model = $this->repo->update($image->id, array(
      'imageable_type' => 'Post',
    ));

    $this->assertEquals('Post', $model->imageable_type);
  }

  public function testAddSize()
  {
    $image = new Image(array(
      'imageable_id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
      'local_sizes' => '200x200',
    ));
    $image->save();

    $model = $this->repo->addSize($image->id, '400x400');

    $this->assertEquals('200x200,400x400', $model->local_sizes, 'Failed to add size.');
  }

  public function testAddSizeBlank()
  {
    $image = new Image(array(
      'imageable_id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
      'local_sizes' => '',
    ));
    $image->save();

    $model = $this->repo->addSize($image->id, '400x400');

    $this->assertEquals('400x400', $model->local_sizes);
  }

  public function testAddSizeList()
  {
    $image = new Image(array(
      'imageable_id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
      'local_sizes' => '200x200,600x600',
    ));
    $image->save();

    $model = $this->repo->addSize($image->id, '400x400');

    $this->assertEquals('200x200,600x600,400x400', $model->local_sizes);
  }

  public function testAddSizeDoNotDuplicate()
  {
    $image = new Image(array(
      'imageable_id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
      'local_sizes' => '200x200,400x400',
    ));
    $image->save();

    $model = $this->repo->addSize($image->id, '400x400');

    $this->assertEquals('200x200,400x400', $model->local_sizes);
  }

  public function testRemoveSize()
  {
    $image = new Image(array(
      'imageable_id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
      'local_sizes' => '200x200',
    ));
    $image->save();

    $model = $this->repo->removeSize($image->id, '200x200');

    $this->assertEquals(null, $model->local_sizes);
  }

  public function testRemoveSizeDoesNotExist()
  {
    $image = new Image(array(
      'imageable_id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
      'local_sizes' => '200x200,300x200',
    ));
    $image->save();

    $model = $this->repo->removeSize($image->id, '100x100');

    $this->assertEquals('200x200,300x200', $model->local_sizes);
  }

}
