<?php
/**
 * Author:  Mark O'Keeffe

 * Date:    18/09/13
 *
 * [Laravel Workbench] ImageFile.php
 */

namespace MOK\ImageUpload;


/**
 * Class ImageFile
 *
 * @package MOK\ImageUpload
 */
class Images {

  /**
   * The application instance.
   *
   * @var \Illuminate\Foundation\Application
   */
  protected $app;

  /**
   * An image manipulation package e.g. Intervention\Image\Image
   *
   * @var ImageManipulationInterface
   */
  protected $im;

  /**
   * The local repository e.g. MySQL database
   *
   * @var Repositories\LocalRepositoryInterface
   */
  protected $local;

  /**
   * The remote repository e.g. Rackspace CDN
   *
   * @var Repositories\RemoteRepositoryInterface
   */
  protected $remote;

  /**
   * Do we use a CDN to automatically upload and serve images?
   *
   * @var bool
   */
  public $useCDN;

  /**
   * Has the image upload modal been added to the HTML body?
   *
   * @var bool
   */
  private $_injected = false;

  public function __construct(
    $app,
    ImageManipulationInterface $im,
    Repositories\LocalRepositoryInterface $local,
    Repositories\RemoteRepositoryInterface $remote)
  {
    $this->app = $app;
    $this->im = $im;
    $this->local = $local;
    $this->remote = $remote;

    $this->useCDN = (
      isset($app['config']['ImageUpload::useCDN'])
      && $app['config']['ImageUpload::useCDN']
    ) ? true : false;

  }

  /**
   * Set the 'useCDN' flag
   *
   * @param bool $useCDN
   */
  public function setUseCDN($useCDN)
  {
    $this->useCDN = $useCDN;
  }

  /**
   * Render an image tag wrapped in a link to fire the image uploader
   *
   * @param        $content
   * @param array  $owner
   * @param array  $attrs Optional data attributes to add to the <a> element
   *
   * @throws \Exception
   * @return \Illuminate\View\View
   */
  public function uploadLink($content, $owner=array(), $attrs=array())
  {
    if (!isset($owner['imageable_type'])) {
      throw new \Exception('Invalid upload link. The owner \'imageable_type\' must be specified.');
    }

    if (!$this->_injected) {
      $this->injectUploadDialogue();
    }

    return \View::make('ImageUpload::_uploadLink', compact('owner', 'content', 'attrs'));
  }

  /**
   * Insert the image upload modal HTML at the end of the page
   */
  private function injectUploadDialogue()
  {
    $view = \View::make('ImageUpload::modal')->render();
    $this->append($view);
    $this->_injected = true;
  }

  /**
   * Append HTML to the page output
   *
   * @param $html
   */
  private function append($html)
  {
    // Get Application instance
    $app = $this->app;

    $app['router']->after(function ($request, $response) use($app, $html)
    {
      // Check that the request is a standard request for a web page
      if( $app->runningInConsole()
        || $request->isXmlHttpRequest()
        || $response->isRedirection()
        || ($response->headers->has('Content-Type') && false === strpos($response->headers->get('Content-Type'), 'html'))
        || 'html' !== $request->getRequestFormat()
      ){
        return;
      }

      if (function_exists('mb_stripos')) {
        $posrFunction   = 'mb_strripos';
        $substrFunction = 'mb_substr';
      } else {
        $posrFunction   = 'strripos';
        $substrFunction = 'substr';
      }

      $content = $response->getContent();
      $pos = $posrFunction($content, '</body>');

      if (false !== $pos) {
        $content = $substrFunction($content, 0, $pos).$html.$substrFunction($content, $pos);
      }else{
        $content = $content . $html;
      }

      $response->setContent($content);

    });
  }

  /**
   * Download an image from a remote URL, saving it to a temporary
   * location and return:
   * - path
   * - extension e.g. 'jpg'
   * - mime e.g. image/jpeg
   *
   * @param $url
   *
   * @throws \Exception
   * @return array
   */
  public function downloadFromUrl($url)
  {
    $ch = curl_init ($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data=curl_exec($ch);

    // Get the HTTP status code of the operation
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Get the MIME type of the file
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

    // Check for a 403 response
    if ($http_status == 403) {
      throw new \Exception('Access to this image file is forbidden.');
    }

    // Output any cURL errors if the result is false
    if (!$data) {
      throw new \Exception(curl_error($ch). ': REST request error - '.$url);
    }

    // Close the connection
    curl_close($ch);

    // Check the MIME type of the image and return an error if
    // it is not a JPEG, GIF or PNG
    switch ($content_type) {
      case 'image/jpeg' : $ext = 'jpg'; break;
      case 'image/gif' : $ext = 'gif'; break;
      case 'image/png' : $ext = 'png'; break;
      default: throw new \Exception('Invalid image type. JPEG, GIF or PNG only!');
    }

    $path = tempnam(sys_get_temp_dir(), 'img');

    $handle = fopen($path, "w");
    fwrite($handle, $data);
    fclose($handle);

    return array(
      'path' => $path,
      'ext' => $ext,
      'mime' => $content_type,
    );
  }

  /**
   * Save an uploaded image performing cropping and resizing where necessary
   *
   * @param      $savePath
   * @param      $tempPath
   * @param      $crop
   * @param null $size
   */
  public function saveUploaded($savePath, $tempPath, $crop, $size = null)
  {
    // Create an image from the uploaded file and crop to the specified dimensions
    $this->im->make($tempPath);

    // Perform the crop
    $this->im->crop(
      $crop['w'],
      $crop['h'],
      $crop['x'],
      $crop['y']
    );

    // Are we scaling the image to a predefined size?
    if (preg_match('/\d*x\d*/', $size)) {
      $size = explode('x', $size);
      $this->im->resize($size[0], $size[1]);
    }

    // Save the file to the filesystem
    $this->im->save($savePath);
  }

  /**
   * Save the image in the desired path
   *
   * @param null|string $path
   * @param             $id
   * @param             $ext
   *
   * @return string
   */
  public function getSavePath($path=null, $id, $ext)
  {
    // Get the base path from the config
    $basePath = $this->getPublicPath($this->app['config']['ImageUpload::dir']);
    // Build the path to the desired save directory
    $savePath = $basePath.'/'.strtolower($path);
    // Create it if necessary
    $this->createDirIfNotExists($savePath);
    // Return the save path with the image name appended
    return $savePath.'/'.$id.'.'.$ext;
  }

  /**
   * Get a public URL for an image of a certain size by resizing and
   * cropping around its focal point if necessary.
   *
   * @param object  $image The image object (id, imageable_type, ext, (sizes))
   * @param string  $size  The desired image size e.g. 640x480
   * @param bool    $save  Do we save the image to file? Or resample on-the-fly?
   *
   * @return string The URL to the image file, or a base64 encoded data src
   */
  public function getUrl($image, $size=null, $save=true)
  {

    // Generate a path to the image, resizing and cropping if necessary
    $url = $this->getDynamicUrl($image, $size, $save);

    if ($this->useCDN) {

      // No size requested? Does the image have a single local size?
      if (!$size && preg_match('/^\d*x\d*$/', $image->local_sizes)) {
        // Use the saved local size as the default
        $getSize = $image->local_sizes;
      } else {
        $getSize = $size;
      }

      // Construct a URL to the image at the CDN
      $url = $this->getRemoteUrl($image, $getSize, true);

      // Has the image been saved at the remote repository already?
      if ($this->imageHasSize($image, $getSize, 'remote_sizes')) {
        return $url;
      }

      // Get the path to the resampled image file
      $path = $this->getPathToSize($image, $size);

      // Generate an image name from the size e.g. 2-640x480.jpg
      $remoteName = $this->getRemoteName($image, $getSize);

      // Does the remote object already exist?
      if ($this->remote->hasObject($remoteName)) {
        // Purge the existing object from the CDN so the
        // new image will be requested
        $this->remote->purgeObject($remoteName);
      }

      // Upload the image to the CDN
      $uploaded = $this->remote->saveObject($remoteName, array(
        'content_type' => $image->mime,
        'path' => $path,
      ));

      if ($uploaded) {
        // Save the size to the 'remote_sizes' for the image
        $this->addSize($image->id, $getSize, 'remote_sizes');

        // Return the URL
        return $url;
      }

    }

    return $url;

  }

  /**
   * Find the path to an image file for the requested size. Resize, crop
   * and save to a new file if necessary.
   *
   * @param $image
   * @param $size
   * @param $save
   *
   * @return string
   */
  public function getDynamicUrl($image, $size, $save)
  {
    // Get the public URL from the config
    $baseURL = $this->getAssetUrl($this->app['config']['ImageUpload::dir']);

    // Ensure the image object has the necessary attributes to find the file
    if (!$this->hasAttrs($image, array('id', 'imageable_type', 'ext'))) {
      // Return a 'not found' image
      return $baseURL.'/'.$this->app['config']['ImageUpload::notFound'];
    }

    // Has no size been requested? Get the original
    if (!$size) {
      return $baseURL.'/'.$this->getOriginalName($image);
    }

    // Check the $image->sizes to see if the desired size is already
    // on the filesystem. If it is - return the public URL
    if ($save && $this->hasAttrs($image, array('local_sizes')) && $this->imageHasSize($image, $size)) {
      return $baseURL.'/'.$this->getSizeName($image, $size);
    }

    // Load the image into the 'intervention' image manipulation
    $originalPath = $this->getOriginalPath($image);
    $this->im->make($originalPath);

    // Get the desired width and height
    list($end_w, $end_h) = explode('x', $size);

    // Check if there is a focal point
    if ($this->hasAttrs($image, array('focal_point'))) {
      list($focal_x, $focal_y) = explode(',', $image->focal_point);
    } else {
      $focal_x = $image->width / 2;
      $focal_y = $image->height / 2;
    }

    // Calculate the resize and crop dimensions
    $dimensions = $this->calculateFocalCrop(
      $this->im->getWidth(),
      $this->im->getHeight(),
      $end_w,
      $end_h,
      $focal_x,
      $focal_y
    );

    // Perform an initial resize
    $this->im->resize($dimensions['resize']['w'], $dimensions['resize']['h']);

    // Does the image need to be cropped to reach the final dimensions?
    if ($dimensions['crop']) {
      // Perform the crop
      $this->im->crop(
        $dimensions['crop']['w'],
        $dimensions['crop']['h'],
        $dimensions['crop']['x'],
        $dimensions['crop']['y']
      );
    }

    // Do we want to save the image to the filesystem?
    if ($save) {

      $basePath = $this->getPublicPath($this->app['config']['ImageUpload::dir']);
      $name = $this->getSizeName($image, $size);
      $savePath = $basePath.'/'.$name;

      // Save to file
      $this->im->save($savePath);

      // Save the size name to the DB
      $this->addSize($image->id, $size);

      // Return the public URL
      return $baseURL.'/'.$name;
    }

    return $this->im->getRawData();
  }

  /**
   * Fetch an image from the database and build a public URL
   *
   * @param object $image     The image, must have id, imageable_type and ext
   * @param bool   $cacheBust Do we need to add a cache busting var?
   *
   * @return string
   */
  public function getOriginalUrl($image, $cacheBust=false)
  {
    // Get the public URL from the config
    $baseURL = $this->getAssetUrl($this->app['config']['ImageUpload::dir']);
    return $baseURL.'/'.$this->getOriginalName($image, $cacheBust);
  }

  /**
   * Get the path to an original image on the file system
   *
   * @param $image
   *
   * @return string
   */
  public function getOriginalPath($image)
  {
    // Get the base path from the config
    $basePath = $this->getPublicPath($this->app['config']['ImageUpload::dir']);
    return $basePath.'/'.$this->getOriginalName($image);
  }

  /**
   * Get the URL to an image in the remote container
   *
   * @param      $image
   * @param      $size
   * @param bool $cacheBust
   *
   * @return string
   */
  public function getRemoteUrl($image, $size, $cacheBust=false)
  {
    // If an image is provided, and has the necessary attributes
    if ($this->hasAttrs($image, array('id', 'ext'))) {
      // Return the name of the image
      return $this->app['config']['ImageUpload::rackspace']['containerUrl']
      .$image->id
      .($size ? '-'.$size : '')
      .'.'.$image->ext
      .($cacheBust ? '?v='.uniqid() : '');
    }
    return $this->app['config']['ImageUpload::notFound'];
  }

  /**
   * Get the path to a saved image of a certain size
   *
   * @param $image
   * @param $size
   *
   * @return string
   */
  public function getPathToSize($image, $size)
  {
    // Get the base path from the config
    $basePath = $this->getPublicPath($this->app['config']['ImageUpload::dir']);
    return $basePath.'/'.$this->getSizeName($image, $size);
  }

  /**
   * Get the name of an image original, or a placeholder image if none is found
   *
   * @param      $image
   * @param bool $cacheBust
   *
   * @return string
   */
  public function getOriginalName($image, $cacheBust=false)
  {
    // If an image is provided, and has the necessary attributes
    if ($this->hasAttrs($image, array('id', 'imageable_type', 'ext'))) {
      // Return the public URL to the image
      return strtolower($image->imageable_type)
        .'/'.$image->id.'.'.$image->ext
        .($cacheBust ? '?v='.uniqid() : '');
    }

    // Return a placeholder image
    return $this->app['config']['ImageUpload::placeholder'];
  }

  /**
   * Get the name of an image file for a certain size for the remote container
   *
   * @param $image
   * @param $size
   *
   * @return string
   */
  public function getRemoteName($image, $size)
  {
    // If an image is provided, and has the necessary attributes
    if ($this->hasAttrs($image, array('id', 'ext'))) {
      // Return the name of the image
      return $image->id
      .($size ? '-'.$size : '')
      .'.'.$image->ext;
    }
    return $this->app['config']['ImageUpload::notFound'];
  }

  /**
   * Get the name of an image file of the desired size, or a 'Not Found'
   * image if the image does not have the desired attributes.
	 *
	 * img/content/user/1.jpg
	 * img/content/user/1-600x400.jpg
   *
   * @param      $image
   * @param      $size
   *
   * @param bool $cacheBust
   *
   * @return string
   */
  public function getSizeName($image, $size, $cacheBust=false)
  {
    // If an image is provided, and has the necessary attributes
    if ($this->hasAttrs($image, array('id', 'imageable_type', 'ext'))) {
      // Return the name of the image
      return strtolower($image->imageable_type)
        .'/'.$image->id
        .($size ? '-'.$size : '')
        .'.'.$image->ext
        .($cacheBust ? '?v='.uniqid() : '');
    }
    return $this->app['config']['ImageUpload::notFound'];
  }

  /**
   * Check if an image object has attributes set and values for those attributes
   *
   * @param $image
   * @param $attrs
   *
   * @return bool
   */
  public function hasAttrs($image, $attrs=array())
  {
    foreach ($attrs as $attr) {
      if (!isset($image->{$attr}) || $image->{$attr} == '') {
        return false;
      }
    }
    return true;
  }

  /**
   * Check an image's saved 'sizes' to see if it has the desired size saved
   *
   * @param        $image
   * @param        $check
   * @param string $attr
   *
   * @return bool
   */
  public function imageHasSize($image, $check, $attr='local_sizes')
  {
    if (!$check) {
      return false;
    }
    // Split the image sizes into an array
    $sizes = explode(',', $image->{$attr});
    // Check each of the current sizes
    foreach ($sizes as $size) {
      // If the check value is already in the current
      if ($size == $check) {
        return true;
      }
    }
    return false;
  }

  /**
   * Calculate the resize and crop dimensions for an image using its focal point
   *
   * @param $full_w
   * @param $full_h
   * @param $end_w
   * @param $end_h
   * @param $focal_x
   * @param $focal_y
   *
   * @return array
   */
  public function calculateFocalCrop($full_w, $full_h, $end_w, $end_h, $focal_x, $focal_y)
  {

    // The calculated dimensions will go here
    $dimensions = array(
      'resize' => array(),
      'crop' => false,
    );

    // Calculate multiplier between original and desired width
    $w_multiplier = $end_w / $full_w;
    // Calculate multiplier between original and desired height
    $h_multiplier = $end_h / $full_h;

    // Y axis is the dominant multiplier, so will result in excess image width
    if ($h_multiplier > $w_multiplier) {
      // Width (x axis) needs to be cropped
      $axis_crop = 'x';
      // Calculate the resize multiplier using the height
      $resize_multiplier = $h_multiplier;
      // X axis is the dominant multiplier, so will result in excess image height
    } elseif ($w_multiplier > $h_multiplier) {
      // Height (y axis) needs to be cropped
      $axis_crop = 'y';
      // Calculate the resize multiplier using the width
      $resize_multiplier = $w_multiplier;
    } else {
      // If the differences match, the aspect ratio has not changed
      // This means no cropping, just a resize!
      $axis_crop = false;
      // Calculate the resize multiplier using the width
      $resize_multiplier = $end_w / $full_w;
    }

    // The desired 'resize' dimensions
    $resize_w = round($full_w * $resize_multiplier);
    $resize_h = round($full_h * $resize_multiplier);

		$focal_x = round($focal_x * $resize_multiplier);
		$focal_y = round($focal_y * $resize_multiplier);

    // Add the initial resize dimensions to the return array
    $dimensions['resize'] = array(
      'w' => $resize_w,
      'h' => $resize_h,
    );

    // Are we cropping the width?
    if ($axis_crop == 'x') {

      // Find left crop point ('x' focal point minus half desired width)
      $crop_l = $focal_x - ($end_w / 2);
      $crop_r = $crop_l + $end_w;
      // The image height is already correct, so no vertical cropping
      $crop_t = 0;

      // If the left crop point is a negative value, set it to 0
      if ($crop_l < 0) {
        $crop_l = 0;
      }
      // If the right crop point is outside the image dimensions
      if ($crop_r > $resize_w) {
        // Calculate the amount needed to bring the right crop point to the
        // rightmost edge of the image
        $crop_l += ($resize_w - $crop_r);
      }

      // If axis_crop == 'h'
    } elseif ($axis_crop == 'y') {

      // Find top crop point ('y' focal point minus half desired height)
      $crop_t = $focal_y - ($end_h / 2);
      $crop_b = $crop_t + $end_h;
      // The image width is already correct, so no horizontal cropping
      $crop_l = 0;

      // If the top crop point is a negative value, set it to 0
      if ($crop_t < 0) {
        $crop_t = 0;
      }
      // If the bottom crop point is outside the image dimensions
      if ($crop_b > $resize_h) {
        // Calculate the amount needed to bring the bottom crop point to the
        // bottom edge of the image
        $crop_t += ($resize_h - $crop_b);
      }

    } else {
      // Return just the resize dimensions, no cropping necessary
      return $dimensions;
    }

    // Add the crop dimensions to the return array
    $dimensions['crop'] = array(
      'w' => $end_w,
      'h' => $end_h,
      'x' => $crop_l,
      'y' => $crop_t,
    );

    return $dimensions;

  }

  /**
   * Use the local repository to add a newly saved image size to the DB
   *
   * @param        $id
   * @param        $size
   * @param string $attr
   */
  public function addSize($id, $size, $attr='local_sizes')
  {
    $this->local->addSize($id, $size, $attr);
  }

  /**
   * Get a URL to an asset in the 'public' dir
   *
   * @param $path
   *
   * @return string
   */
  public function getAssetUrl($path)
  {
    return (string) asset($path);
  }

  /**
   * Get the full path to an image file in the 'public' dir
   *
   * @param $path
   *
   * @return string
   */
  public function getPublicPath($path)
  {
    return public_path($path);
  }

  /**
   * Create a directory if it does not exist on the file system
   *
   * @param $path
   *
   * @return bool
   */
  public function createDirIfNotExists($path)
  {
    if (!file_exists($path)) {
      return mkdir($path, 0777, true);
    }
    return true;
  }

}
