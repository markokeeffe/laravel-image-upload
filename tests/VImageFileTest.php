<?php

use Mockery as m;
use Way\Tests\Factory;

class VImageFileTest extends TestCase {

  /**
   * VImageFile class
   *
   * @var $vf
   */
  protected $vf;

  /**
   * Mock of ImageManipulationInterface
   *
   * @var $im
   */
  protected $im;

  /**
   * The remote repository e.g. Rackspace CDN
   *
   * @var MOK\ImageUpload\Repositories\LocalRepositoryInterface
   */
  protected $local;

  /**
   * The remote repository e.g. Rackspace CDN
   *
   * @var MOK\ImageUpload\Repositories\RemoteRepositoryInterface
   */
  protected $remote;

  public function setUp()
  {
    $this->im = $this->mockIM();
    $this->local = $this->mockLocal();
    $this->remote = $this->mockRemote();

    parent::setUp();

    $this->vf = new MOK\ImageUpload\VImageFile(
      $this->app,
      $this->im,
      $this->local,
      $this->remote
    );
    $this->vf->setUseCDN(false);

  }

  /**
   * Check setting the 'useCDN' flag
   */
  public function testSetUseCDN()
  {
    $useCDN = $this->vf->useCDN;
    $this->vf->setUseCDN(($useCDN ? false : true));

    $this->assertNotEquals($useCDN, $this->vf->useCDN);
  }

//  public function testDownloadFromURL()
//  {
//    $vf = new MOK\ImageUpload\VImageFile(
//      $this->app,
//      $this->app['MOK\ImageUpload\ImageManipulationInterface'],
//      $this->app['MOK\ImageUpload\Repositories\LocalRepositoryInterface'],
//      $this->app['MOK\ImageUpload\Repositories\RemoteRepositoryInterface']
//    );
//
//    $expected = array(
//      'path' => 'tmp/laravel.jpg',
//      'ext' => 'jpg',
//      'mime' => 'image/jpeg',
//    );
//
//    $actual = $vf->downloadFromUrl('http://www.rashkeed.com/wp-content/uploads/2013/07/laravdel.jpg');
//    $this->assertEquals($expected, $actual, 'Image download from URL unsuccessful');
//  }

  /**
   * Test getSavePath()
   */
  public function testGetSavePath()
  {

    // Need to mock two internal methods that use global dependencies
    $m = m::mock(
      '\MOK\ImageUpload\VImageFile[getPublicPath, createDirIfNotExists]',
      array($this->app, $this->im, $this->local, $this->remote)
    );

    // Uses PHP's 'file_exists()' and 'mkdir()'
    $m->shouldReceive('createDirIfNotExists')
      ->with(m::type('string'))
      ->once()
      ->ordered('getSavePath');

    // Uses Laravel's 'pubilc_path()'
    $m->shouldReceive('getPublicPath')
      ->with($this->app['config']['ImageUpload::dir'])
      ->once()
      ->ordered('getSavePath')
      ->andReturn('fsPath/'.$this->app['config']['ImageUpload::dir']);

    // Act
    $actual = $m->getSavePath('User', 3, 'jpg');

    // Assert
    $expected = 'fsPath/'.$this->app['config']['ImageUpload::dir'].'/user/3.jpg';
    $this->assertEquals($expected, $actual, 'Generated save path does not match.');
  }

  /**
   * Test the generation of a public URL to an image
   * from the 'Image' model instance
   */
  public function testGetOriginalUrl()
  {
    // Need to mock two internal methods that use global dependencies
    $m = m::mock('\MOK\ImageUpload\VImageFile[getAssetUrl]',
      array($this->app, $this->im, $this->local, $this->remote)
    );

    // Uses Laravel's 'asset()'
    $m->shouldReceive('getAssetUrl')
      ->with($this->app['config']['ImageUpload::dir'])
      ->once()
      ->ordered('getPublicUrl')
      ->andReturn('asset/'.$this->app['config']['ImageUpload::dir']);

    // Arrange
    $image = Factory::make('MOK\ImageUpload\Models\Image', array(
      'id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
    ));

    // Act
    $actual = $m->getOriginalUrl($image);

    // Assert
    $expected = 'asset/'.$this->app['config']['ImageUpload::dir'].'/user/1.jpg';
    $this->assertEquals($expected, $actual, 'Generated public URL does not match.');

  }

  /**
   * Test the generation of a path to an image file
   * from the 'Image' model instance
   */
  public function testGetOriginalPath()
  {
    // Need to mock two internal methods that use global dependencies
    $m = m::mock('\MOK\ImageUpload\VImageFile[getPublicPath]',
      array($this->app, $this->im, $this->local, $this->remote)
    );

    // Uses Laravel's 'pubilc_path()'
    $m->shouldReceive('getPublicPath')
      ->with($this->app['config']['ImageUpload::dir'])
      ->once()
      ->ordered('getOriginalPath')
      ->andReturn('puburl/'.$this->app['config']['ImageUpload::dir']);

    // Arrange
    $image = Factory::make('MOK\ImageUpload\Models\Image', array(
      'id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
    ));

    // Act
    $actual = $m->getOriginalPath($image);

    // Assert
    $expected = 'puburl/'.$this->app['config']['ImageUpload::dir'].'/user/1.jpg';
    $this->assertEquals($expected, $actual, 'Generated public path does not match.');

  }

  /**
   * Test the generation of a path to an image file
   * from the 'Image' model instance
   */
  public function testGetPathToSize()
  {
    // Need to mock two internal methods that use global dependencies
    $m = m::mock('\MOK\ImageUpload\VImageFile[getPublicPath]',
      array($this->app, $this->im, $this->local, $this->remote)
    );

    // Uses Laravel's 'pubilc_path()'
    $m->shouldReceive('getPublicPath')
      ->with($this->app['config']['ImageUpload::dir'])
      ->once()
      ->ordered('getOriginalPath')
      ->andReturn('puburl/'.$this->app['config']['ImageUpload::dir']);

    // Arrange
    $image = Factory::make('MOK\ImageUpload\Models\Image', array(
      'id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
    ));

    // Act
    $actual = $m->getPathToSize($image, '400x200');

    // Assert
    $expected = 'puburl/'.$this->app['config']['ImageUpload::dir'].'/user/1-400x200.jpg';
    $this->assertEquals($expected, $actual, 'Generated public path does not match.');

  }

  /**
   * Test the generation of an image name from the 'Image' model instance
   */
  public function testGetOriginalName()
  {

    // Arrange
    $image = Factory::make('MOK\ImageUpload\Models\Image', array(
      'id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
    ));

    // Act
    $actual = $this->vf->getOriginalName($image);

    // Assert
    $expected = 'user/1.jpg';
    $this->assertEquals($expected, $actual, 'Generated image name not match.');

  }

  /**
   * Test the generation of a public URL to an image
   * from the 'Image' model instance
   */
  public function testGetRemoteUrl()
  {
    // Arrange
    $image = Factory::make('MOK\ImageUpload\Models\Image', array(
      'id' => 1,
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
    ));
    // Act
    $actual = $this->vf->getRemoteUrl($image, '200x200');

    // Assert
    $expected = $this->app['config']['ImageUpload::rackspace']['containerUrl'].'1-200x200.jpg';
    $this->assertEquals($expected, $actual, 'Generated remote URL does not match.');

  }

  /**
   * Test the generation of an image name from the 'Image' model instance
   * including the desired size
   */
  public function testGetRemoteName()
  {

    // Arrange
    $image = Factory::make('Image', array(
      'id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
    ));

    // Act
    $actual = $this->vf->getRemoteName($image, '300x200');

    // Assert
    $expected = '1-300x200.jpg';
    $this->assertEquals($expected, $actual, 'Generated image name not match.');

  }

  /**
   * Test the generation of an image name from the 'Image' model instance
   * including the desired size
   */
  public function testGetSizeName()
  {

    // Arrange
    $image = Factory::make('Image', array(
      'id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
    ));

    // Act
    $actual = $this->vf->getSizeName($image, '300x200');

    // Assert
    $expected = 'user/1-300x200.jpg';
    $this->assertEquals($expected, $actual, 'Generated image name not match.');

  }

  /**
   * Test the generation of an image name from the 'Image' model instance
   * including the desired size
   */
  public function testGetSizeNameNoSize()
  {

    // Arrange
    $image = Factory::make('Image', array(
      'id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
    ));

    // Act
    $actual = $this->vf->getSizeName($image, false);

    // Assert
    $expected = 'user/1.jpg';
    $this->assertEquals($expected, $actual, 'Generated image name not match.');

  }

  /**
   * Test finding values for an array of attribute names in a class
   */
  public function testHasAttrsFound()
  {
    // Arrange
    $image = Factory::make('Image', array(
      'id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
    ));

    // Act
    $actual = $this->vf->hasAttrs($image, array('id', 'imageable_type', 'ext'));

    // Assert
    $expected = true;
    $this->assertEquals($expected, $actual, 'Failed to recognize provided attributes');
  }

  /**
   * Test returning false when one of requested attributes is not found
   */
  public function testHasAttrsNotFound()
  {
    // Arrange
    $image = Factory::make('Image', array(
      'id' => 1,
      'imageable_type' => null,
    ));

    // Act
    $actual = $this->vf->hasAttrs($image, array('id', 'imageable_type', 'ext'));

    // Assert
    $expected = false;
    $this->assertEquals($expected, $actual, 'Failed to recognize missing attributes');
  }

  /**
   * Test finding a requested size in an image's list of sizes
   */
  public function testImageHasSizeFound()
  {
    // Arrange
    $image = Factory::make('Image', array(
      'id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
      'local_sizes' => '300x200,500x500,120x694',
    ));

    // Act
    $actual = $this->vf->imageHasSize($image, '300x200');

    // Assert
    $this->assertTrue($actual, 'Failed to recognize provided image size');
  }

  /**
   * Test returning false when a requested size is not in an image's list of sizes
   */
  public function testImageHasSizeNotFound()
  {
    // Arrange
    $image = Factory::make('Image', array(
      'id' => 1,
      'imageable_type' => 'User',
      'ext' => 'jpg',
      'mime' => 'image/jpeg',
      'local_sizes' => '500x500,120x694',
    ));

    // Act
    $actual = $this->vf->imageHasSize($image, '300x200');

    // Assert
    $expected = false;
    $this->assertEquals($expected, $actual, 'Failed to recognize missing image size');
  }

  /**
   * Test calculating a resize/crop with focal point
   */
  public function testCalculateFocalCropCorrect()
  {
    // Act
    $actual = $this->vf->calculateFocalCrop(
      200, // Original Width
      300, // Original Height
      200, // Desired Width
      100, // Desired Height
      100, // Focal X
      250  // Focal Y
    );

    $expected = array(
      'resize' => array(
        'w' => 200, // Width
        'h' => 300, // Height
      ),
      'crop' => array(
        'w' => 200, // Desired Width
        'h' => 100, // Desired Height
        'x' => 0,  // Crop X
        'y' => 200,   // Crop Y
      ),
    );

    // Assert
    $this->assertEquals($expected, $actual, 'Failed to calculate correct crop with focal point');
  }

  /**
   * Check calculating a crop/resize that does not need to crop the image
   */
  public function testCalculateFocalCropNoCrop()
  {
    // Act
    $actual = $this->vf->calculateFocalCrop(
      300, // Original Width
      200, // Original Height
      600, // Desired Width
      400, // Desired Height
      150, // Focal X
      100  // Focal Y
    );

    $expected = array(
      'resize' => array(
        'w' => 600, // Width
        'h' => 400, // Height
      ),
      'crop' => false,
    );

    // Assert
    $this->assertEquals($expected, $actual, 'Failed to calculate correct resize with no crop');
  }

  /**
   * Mock the ImageManipulationInterface
   *
   * @return m\MockInterface|Yay_MockObject
   */
  public function mockIM()
  {
    $m = m::mock('MOK\ImageUpload\ImageManipulationInterface');
    $m->shouldReceive('all')->once();
    return $m;
  }

  /**
   * Mock the LocalRepositoryInterface
   *
   * @return m\MockInterface|Yay_MockObject
   */
  public function mockLocal()
  {
    $m = m::mock('MOK\ImageUpload\Repositories\LocalRepositoryInterface');
    $m->shouldReceive('all')->once();
    return $m;
  }

  /**
   * Mock the RemoteRepositoryInterface
   *
   * @return m\MockInterface|Yay_MockObject
   */
  public function mockRemote()
  {
    $m = m::mock('MOK\ImageUpload\Repositories\RemoteRepositoryInterface', array($this->app));
    $m->shouldReceive('all')->once();
    return $m;
  }

}
