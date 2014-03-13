laravel-image-upload
====================

An AJAX image uploader with dynamic resizing and CDN integration.

## Dependencies ##
Currently configured to work with the Rackspace Cloud Files CDN. This implementation can be swapped out by programming a new class to the
`MOK\ImageUpload\Repositories\RemoteRepositoryInterface.`

## Installation ##

Add the following to the `require` object in your `composer.json`:

```json
  "require": {
    ...
    "markokeeffe/image-upload": "dev-trunk"
  },
```

Update composer:

```bash
$ composer update
```

Add the Image Upload service provider in `config/app.php`:

```php
'providers' => array(
    ...
    'MOK\ImageUpload\ImageUploadServiceProvider',
),
```

Add the VIFile and VIHTML facades:

```php
'aliases' => array(
    ...
    'Images' => 'MOK\ImageUpload\Facades\Images',
),
```

Publish the config:

```bash
$ php artisan config:publish markokeeffe/image-upload
```

Publish the assets:

```bash
$ php artisan asset:publish markokeeffe/image-upload
```

## Image Model ##

Execute the package migration to create an `images` table:

```bash
$ php artisan migrate --package=markokeeffe/image-upload
```

Create an `Image` model with polymorphic capability:

```php
class Image extends Eloquent {

	protected $guarded = array('id');

  public function imageable()
  {
    return $this->morphTo();
  }

}
```

You can configure any of your other models to 'own' images by adding a polymorphic relationship in your model of choice e.g.

```php
class User extends Eloquent {

  public function images()
  {
    return $this->morphMany('Image', 'imageable');
  }

}
```
## Upload Links ##

You must have at least one model that is 'imageable' as per the instructions above. Upload links can be generated with the `VIHTML::uploadLink()` method.

The first parameter is the link content.

The second parameter is an array specifying information about the image owner:

| Name | Example | Description |
| --- | --- | --- |
| `imageable_type` | `'User'` | *Required* The class name of the image owner |
| `imageable_id` | `4` | The ID of the image owner e.g. `$user->id` |


The third parameter is an array of HTML5 data attributes that will be passed to the image uploader:

| Name | Example | Description |
| --- | --- | --- |
| `size` | `'640x480'` | The desired pixel dimensions of the image. If set, this will fix the crop aspect ratio and save the uploaded image to the desired size. If size is omitted, the user will select a crop of any size & shape as well as a focal point. Images with a focal point can be resized to any dimensions on-the-fly. |
| `display-container` | `'.user-images'` | The CSS selector of a container where a newly uploaded image can be appended upon completion. |
| `display-size` | `'300x300'` | The dimensions used for the uploaded image added to the `display-container` (see above). |
| `image-selector` | `'#user-img-4'` | The CSS selector of an `<img>` tag that will be updated to display the uploaded image. |
| `hidden-selector` | `'#user-img-id-4'` | The CSS selector of an `<input>` tag that will be updated with the ID of the new image. |
| `alert` | `'<strong>Image Uploaded</strong> Please Save!'` | An alert message to be displayed after uploading is complete. This must be combined with the `alert-container` and `alert-class` parameters. |
| `alert-container` | `'.alert-container'` | The CSS selector of an element on the page that an alert message can be inserted into. |
| `alert-class` | `'alert alert-danger'` | A CSS class to add to an alert that will be inserted into the alert container. |


## Examples ##

### Upload for an existing user ###

```php
Images::uploadLink(
  '<img id="user-img-'.$image->id.'" src="'.Images::getUrl($image, '400x300').'" />',
  array(
    'imageable_type' => 'User',
    'imageable_id' => $user->id,
  ),array(
    'image-id' => $image->id,
    'image-selector' => '#user-img-'.$image->id,
  )
);
```

This will display the current image for the user (or a placeholder if no image exists). The image is clickable to upload a new image for the user.

### Upload for an existing user with fixed size and display container ###

```php
<div class="user-images"></div>
<?php
Images::uploadLink('<button class="btn btn-default">New Image</button>', array(
  'imageable_type' => 'User',
  'imageable_id' => $user->id,
), array(
  'display-container' => '.user-images',
  'size' => '640x480',
));
?>
```

This uses a 'New Image' button with the 'display-container' and 'size' options. When the image is uploaded, the saved 640x480 version will be added to the`.user-images` div.

### Upload while creating a new user ###


```php
<div class="user-images"></div>
<input type="hidden" id="user-img-id" name="img_id" value="" />
<?php
Images::uploadLink('<button class="btn btn-default">New Image</button>', array(
  'imageable_type' => 'User',
), array(
  'hidden-selector' => '#user-img-id',
  'display-container' => '.user-images',
  'alert' => '<strong>Image Uploaded</strong> You must save this user to associate it with the image.',
  'alert-class' => 'alert alert-danger',
  'alert-container' => '.alert-container',
));
?>
```

A hidden field is used to store the image ID. When the new user is saved, this ID can be used to update the `imageable_id` attribute in the `images` table with the `id` attribute of the user.

When uploading images in this way, the relationship between user and image cannot be created until the form is submitted and processed. To aid the user, an alert message can be specified using the `alert`, `alert-class` and `alert-container` options.

This will display an alert to the user when image upload is complete to remind them to save.

## Dynamic Resizing ##

Images that are uploaded without a fixed size can be requested with any dimensions. They will be dynamically resized, taking a specified focal point into account to try and avoid cropping the most important part of the image.

To request a dynamically resized image, use the `Images::getUrl()` method:

```php
<?php
$image = Image::find(1);
?>
<img src="<?php echo Images::getUrl($image, '400x300'); ?>" />
```

This will first check the `$image` object to see if the '400x300' size has been saved already. If it has, the URL to the file is returned instantly. If this is the first time a '400x300' version of the image is requested, the Images class will resize and crop the image to the desired dimensions, save it to file, update the `local_sizes` attribute of the image with the new size and return the public URL to the newly saved image.
