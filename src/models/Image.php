<?php namespace MOK\ImageUpload\Models;

use Eloquent;

class Image extends Eloquent {

	protected $guarded = array('id');

	public static $rules = array();

  public function imageable()
  {
    return $this->morphTo();
  }

}
