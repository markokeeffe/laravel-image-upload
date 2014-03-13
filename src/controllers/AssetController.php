<?php namespace MOK\ImageUpload;

use \Illuminate\Routing\Controllers\Controller;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;
use Assetic\Filter\CssMinFilter;
use Assetic\Filter\JsMinFilter;
use Response;
use Config;

class AssetController extends Controller
{

  public $assetPath = 'packages/markokeeffe/image-upload/assets/';

  /**
   * Minify and concatenate the CSS
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function css()
  {
    $assets = array();
    foreach (Config::get('ImageUpload::assets.css') as $file) {
      $assets[] = new FileAsset(public_path($this->assetPath.'css/'.$file));
    }
    $css = new AssetCollection($assets, array(
      new CssMinFilter(),
    ));

    $response = Response::make($css->dump(), 200);
    $response->header('Content-Type', 'text/css');
    return $response;
  }

  /**
   * Minify and concatenate the JS
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function js()
  {
    $assets = array();
    foreach (Config::get('ImageUpload::assets.js') as $file) {
      if (preg_match('/\*$/', $file)) {
        $assets[] = new GlobAsset(public_path($this->assetPath.'js/'.$file));
      } else {
        $assets[] = new FileAsset(public_path($this->assetPath.'js/'.$file));
      }
    }

    $js = new AssetCollection($assets, array(
      new JsMinFilter(),
    ));

    $response = Response::make($js->dump(), 200);
    $response->header('Content-Type', 'application/javascript');
    return $response;
  }

}
