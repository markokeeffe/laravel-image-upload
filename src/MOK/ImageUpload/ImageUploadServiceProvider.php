<?php namespace MOK\ImageUpload;

use Intervention\Image\Image;
use Illuminate\Support\ServiceProvider;

class ImageUploadServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{

    $this->package('MOK/ImageUpload');

    require_once __DIR__.'/../../routes.php';

    $this->publishAssets();

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{

    // Create an instance of the \Intervention\Image\Image class
    $this->app->bind('Intervention', function(){
      return new Image;
    });

    $this->app->bind('MOK\ImageUpload\ImageManipulationInterface', function($app){
      return new InterventionImageManipulation($app['Intervention']);
    });

    // Bind the DatabaseRepository to the LocalRepositoryInterface
    $this->app->bind('MOK\ImageUpload\Repositories\LocalRepositoryInterface',
      'MOK\ImageUpload\Repositories\DatabaseRepository');

    // Bind the RackspaceRepository to the RemoteRepositoryInterface
    $this->app->bind('MOK\ImageUpload\Repositories\RemoteRepositoryInterface', function($app){
      return new Repositories\RackspaceRepository($app);
    });

    $this->app->singleton('images', function($app){
      return new Images($app,
        $app['MOK\ImageUpload\ImageManipulationInterface'],
        $app['MOK\ImageUpload\Repositories\LocalRepositoryInterface'],
        $app['MOK\ImageUpload\Repositories\RemoteRepositoryInterface']
      );
    });
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

  /**
   * Publish any CSS, JS and other assets this package needs
   */
  private function publishAssets()
  {
    // Auto-publish the assets when developing locally
    if ($this->app->environment() == 'local' && !$this->app->runningInConsole()) {
      $workbench = realpath(base_path().'/workbench');
      if (strpos(__FILE__, $workbench) === false) {
        $this->app->make('asset.publisher')->publishPackage('markokeeffe/image-upload');
      } else {
        $this->app->make('asset.publisher')->publishPackage('markokeeffe/image-upload', $workbench);
      }
    }
  }

}
