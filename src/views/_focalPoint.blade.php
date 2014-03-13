<div class="row">
  <div class="col-md-12 focal-button-container">
    <button class="btn btn-success" id="VImageUploader-set-focal" style="display: none">Set Focal Point</button>
  </div>
</div>


<div class="row">
  <div class="col-md-12 focal-container">
    <img src="{{ $src }}" alt="Set focal point"
      data-behavior="focalPoint"
      data-button="#VImageUploader-set-focal"
      data-action="{{ action('MOK\ImageUpload\ImageController@focalPoint', array(
        'id' => $id,
      )) }}"/>
  </div>
</div>
