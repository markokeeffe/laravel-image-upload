<?php

// Get the handle config item
$handle = Config::get('ImageUpload::handle');

Route::group(array('prefix' => $handle), function(){

  Route::any('form', 'MOK\ImageUpload\ImageController@form');
  Route::post('upload', 'MOK\ImageUpload\ImageController@upload');
  Route::post('download', 'MOK\ImageUpload\ImageController@download');
  Route::post('focalPoint/{id?}', 'MOK\ImageUpload\ImageController@focalPoint');

});

Route::get('packages/markokeeffe/image-upload/assets/css/min.css', 'MOK\ImageUpload\AssetController@css');
Route::get('packages/markokeeffe/image-upload/assets/js/min.js', 'MOK\ImageUpload\AssetController@js');
