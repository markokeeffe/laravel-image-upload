<?php namespace MOK\ImageUpload;

use \Illuminate\Routing\Controllers\Controller;
use App;
use View;
use Response;
use Input;
use Images;

class ImageController extends Controller
{

  /**
   * The local repository e.g. Database
   *
   * @var Repositories\LocalRepositoryInterface
   */
  protected $local;

  public function __construct(
    Repositories\LocalRepositoryInterface $local)
  {
    $this->local = $local;
  }

  /**
   * Load the image upload form
   *
   * @throws \Exception
   * @internal param $owner
   *
   * @return \Response
   */
  public function form()
  {
    // Check the required 'imageable_type' parameter has been provided
    if (!Input::has('imageable_type')) {
      throw new \Exception('Unable to start image upload, \'imageable_type\' required.');
    }

    // Set up some HTML5 'data-' attributes for the uploader form
    $formData = array(
      'data-imageable-type' => Input::get('imageable_type'),
    );
    if (Input::has('imageable_id')) {
      $formData['data-imageable-id'] = Input::get('imageable_id');
    }

    $body = View::make('ImageUpload::_uploadForm', compact('formData'))->render();
    $modal = array(
      'heading' => 'Upload Image',
      'body' => $body,
    );

    return Response::json(array(
			'type' => 'modal',
			'msg' => $modal,
		));
  }

  /**
   * Upload an image file and perform the specified crop and resize
   *
   * @return Response
   */
  public function upload()
  {

    // Check that a file has been uploaded, and that the crop dimensions exist
    if (!Input::hasFile('image')) {
      App::abort(400, 'Unable to upload. No image provided.');
    }

    // Get the file object for the uploaded image
    $file = Input::file('image');

    $imagePath = $file->getRealPath();
    $ext = $file->getClientOriginalExtension();
    $mime = $file->getClientMimeType();

    return $this->handleImage($imagePath, $ext, $mime);

  }

  /**
   * Download an image from a remote URL
   * and perform the specified crop and resize
   *
   * @return Response
   */
  public function download()
  {
    // Check that a file has been uploaded, and that the crop dimensions exist
    if (!$url = Input::get('url')) {
      App::abort(400, 'Unable to download. No image URL provided.');
    }

    try {
      $image = Images::downloadFromUrl($url);
    } catch (\Exception $e) {
      App::abort(400, 'Unable to download. '.$e->getMessage());
    }

    return $this->handleImage($image['path'], $image['ext'], $image['mime']);

  }

  /**
   * Crop, resize and save an image file
   *
   * @param $imagePath
   * @param $ext
   * @param $mime
   *
   * @return \Illuminate\Http\JsonResponse
   */
  public function handleImage($imagePath, $ext, $mime)
  {
    // Get the content type name
    if (!$imageableType = Input::get('imageableType')) {
      App::abort(400, 'Unable to upload. Invalid image owner class.');
    }

    // Get the crop dimensions from the POSTed data
    if (!$crop = Input::get('crop')) {
      App::abort(400, 'Unable to upload. No co-ordinates provided.');
    }

    $data = array(
      'ext' => $ext,
      'mime' => $mime,
      'imageable_type' => $imageableType,
    );

    // Has an image owner ID been provided? Set it
    if (Input::has('imageableId')) {
      $data['imageable_id'] = Input::get('imageableId');
    }

    // Has a size been specified? Add it to the save data
    if (Input::has('size')) {
      $data['local_sizes'] = $size = Input::get('size');
      $data['remote_sizes'] = null;
    } else {
      $data['local_sizes'] = $size = null;
    }

    // Has an image ID been submitted? Does an image exist with that ID?
    if (Input::has('imageId') && $this->local->get(Input::get('imageId'))) {
      // Update the image to set the extension and 'imageable_type' if necessary
      $image = $this->local->update(Input::get('imageId'), $data);
    } else {
      // Add the image to the database, saving the extension and 'imageable type'
      $image = $this->local->add($data);
    }

    // Build a save path for this image file
    $savePath = Images::getSavePath(
      strtolower($imageableType), // Directory name e.g. 'user'
      $image->id, // Numerical ID (used as image name)
      $ext // File extension (jpg|gif|png)
    );

    // Save the uploaded file to the save path, cropping & resizing if necessary
    Images::saveUploaded(
      $savePath,
      $imagePath,
      $crop,
      $size
    );

    // Get the public URL to the image file with a cache busting var
    $publicUrl = Images::getOriginalUrl($image, true);

    // Return the ID and public image URL to the browser to set the focal point
    $params = array(
      'id' => $image->id,
      'src' => $publicUrl,
    );

    // Is the size of the image fixed? No need for a focal point
    if (Input::has('size')) {
      return Response::json(array(
        'type' => 'success',
        'msg' => $params,
      ));
    }

    $body = View::make('ImageUpload::_focalPoint', $params)->render();
    $modal = array(
      'heading' => 'Set Focal Point',
      'body' => $body,
    );

    return Response::json(array(
      'type' => 'modal',
      'msg' => $modal,
    ));
  }

  /**
   * Save a chosen focal point for a cropped image
   *
   * @param $id
   * @return \Illuminate\Http\JsonResponse
   */
  public function focalPoint($id)
  {
    // Get the content type name
    if (!$focalPoint = Input::get('focalPoint')) {
      App::abort(400, 'Unable to save focal point. No co-ordinates provided');
    }

    $image = $this->local->update($id, array(
      'focal_point' => $focalPoint,
    ));

    return Response::json(array(
      'type' => 'success',
      'msg' => array(
        'id' => $id,
        'src' => Images::getOriginalUrl($image, true),
      ),
    ));

  }

}
