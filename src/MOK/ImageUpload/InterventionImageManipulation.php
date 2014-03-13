<?php namespace MOK\ImageUpload;

use Intervention\Image\Image;

class InterventionImageManipulation implements ImageManipulationInterface {

  /**
   * The Intervention Image class
   *
   * @var \Intervention\Image\Image
   */
  protected $i;

  /**
   * Assign the Image class
   *
   * @param Image $i
   */
  public function __construct(Image $i)
  {
    $this->i = $i;
  }

  /**
   * Make a new image instance
   *
   */
  public function make($path)
  {
    // Return the instance
    return $this->i = $this->i->make($path);
  }

  /**
   * Save the image instance to file
   *
   * @param     $path
   * @param int $quality
   *
   * @return Image
   */
  public function save($path, $quality=90)
  {
    return $this->i = $this->i->save($path, $quality);
  }

  /**
   * Perform a crop with the specified dimensions
   *
   * @param int $w
   * @param int $h
   * @param int $x
   * @param int $y
   *
   * @return Image
   */
  public function crop($w, $h, $x=0, $y=0)
  {
    return $this->i = $this->i->crop($w, $h, $x, $y);
  }

  /**
   * Perform a resize with the specified dimensions
   *
   * @param int $w
   * @param int $h
   *
   * @return Image
   */
  public function resize($w, $h)
  {
    return $this->i = $this->i->resize($w, $h);
  }

  /**
   * Return the width of the current image instance
   *
   * @return int
   */
  public function getWidth()
  {
    return $this->i->width;
  }

  /**
   * Return the height of the current image instance
   *
   * @return int
   */
  public function getHeight()
  {
    return $this->i->height;
  }

  /**
   * Return the raw base64 encoded image data
   */
  public function getRawData()
  {
    return 'data:'.$this->i->mime.';base64,'.base64_encode((string) $this->i);
  }

  public function getPath()
  {
    return $this->i->dirname.$this->i->filename;
  }

  public function getExt()
  {
    return $this->i->extension;
  }

  public function getMime()
  {
    return $this->i->mime;
  }


}
