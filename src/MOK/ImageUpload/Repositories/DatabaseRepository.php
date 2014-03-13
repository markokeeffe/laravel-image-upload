<?php namespace MOK\ImageUpload\Repositories;

use \MOK\ImageUpload\Models\Image;

class DatabaseRepository implements LocalRepositoryInterface
{

  /**
   * Find an image from the database
   *
   * @param $id
   *
   * @return \MOK\ImageUpload\Models\Image
   */
  public function get($id)
  {
    return Image::find($id);
  }

  /**
   * Save a new image to the database
   *
   * @param $data
   *
   * @return \MOK\ImageUpload\Models\Image
   */
  public function add($data)
  {
    $image = new Image($data);
    $image->save();
    return $image;
  }

  /**
   * Update an image in the database from an array of attributes
   *
   * @param $id
   * @param $data
   *
   * @return \MOK\ImageUpload\Models\Image
   */
  public function update($id, $data)
  {
    $image = $this->get($id);
    $image->update($data);
    return $image;
  }

  /**
   * Add a new saved image size to the list of sizes
   *
   * @param        $id
   * @param        $size
   * @param string $attr
   *
   * @return Image
   */
  public function addSize($id, $size, $attr='local_sizes')
  {
    $image = $this->get($id);
    $image->{$attr} = $this->editSizes($image->{$attr}, $size, 'add');
    $image->save();
    return $image;
  }

  /**
   * Add a new saved image size to the list of sizes
   *
   * @param        $id
   * @param        $size
   * @param string $attr
   *
   * @return Image
   */
  public function removeSize($id, $size, $attr='local_sizes')
  {
    $image = $this->get($id);
    $image->{$attr} = $this->editSizes($image->{$attr}, $size, 'remove');
    $image->save();
    return $image;
  }

  /**
   * Edit the list of sizes for an image by adding or removing a size
   *
   * @param $currentSizes
   * @param $size
   * @param $action
   *
   * @internal param $append
   *
   * @return string
   */
  public function editSizes($currentSizes, $size, $action)
  {
    // No sizes currently? Just return the new size value
    if ($action == 'add' && !$currentSizes) {
      return $size;
    }

    // Split the current sizes into an array
    $sizes = explode(',', $currentSizes);
    // Check each of the current sizes
    foreach ($sizes as $i => $s) {
      // If the $size value is already in the current
      if ($s == $size) {
        if ($action === 'add') {
          // We don't need to add it again!
          return $currentSizes;
        } elseif ($action === 'remove') {
          // Remove the desired size from the array
          unset($sizes[$i]);
        }
      }
    }

    if ($action == 'add') {
      // Add the append value to the array
      $sizes[] = $size;
    }

    // Flatten the array to CSV and return
    return implode(',', $sizes);
  }

}
