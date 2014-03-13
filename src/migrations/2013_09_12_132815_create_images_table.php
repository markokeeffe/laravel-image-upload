<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;


class CreateImagesTable extends Migration {
	public function up()
	{
		Schema::create('images', function(Blueprint $table) {
			$table->increments('id');
			$table->string('imageable_type')->index()->nullable();
			$table->integer('imageable_id')->unsigned()->index()->nullable();
      $table->string('ext', 3);
      $table->string('mime', 16);
			$table->string('focal_point', 11)->nullable();
			$table->string('local_sizes', 1023)->nullable();
      $table->string('remote_sizes', 1023)->nullable();
			$table->string('alt', 255)->nullable();
			$table->string('caption', 1023)->nullable();
			$table->tinyInteger('status')->unsigned()->default(0);
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::drop('images');
	}
}