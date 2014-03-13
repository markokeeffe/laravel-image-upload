<div id="VImageUploader" class="modal fade" data-behavior="imageUploadModal">
  <div class="modal-dialog">
    <div class="modal-content">

    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php
if (App::environment() === 'production') {
  echo '<link href="'.action('MOK\ImageUpload\AssetController@css').'" rel="stylesheet">'.PHP_EOL;
  echo '<script type="text/javascript" src="'.action('MOK\ImageUpload\AssetController@js').'"></script>'.PHP_EOL;
} else {
  $dir = 'packages/markokeeffe/image-upload/assets/';
  $cssDir = $dir.'css/';
  $jsDir = $dir.'js/';
  $jsPath = public_path($jsDir);

  foreach (Config::get('ImageUpload::assets.css') as $cssFile) {
    echo '<link href="'.asset($cssDir.$cssFile).'" rel="stylesheet">'.PHP_EOL;
  }

  foreach (Config::get('ImageUpload::assets.js') as $jsFile) {
    if (preg_match('/\*$/', $jsFile)) {
      foreach (glob($jsPath.$jsFile) as $jsFile2) {
        $jsFile2 = str_replace($jsPath, '', $jsFile2);
        echo '<script type="text/javascript" src="'.asset($jsDir.$jsFile2).'"></script>'.PHP_EOL;
      }
    } else {
      echo '<script type="text/javascript" src="'.asset($jsDir.$jsFile).'"></script>'.PHP_EOL;
    }
  }
}
?>
