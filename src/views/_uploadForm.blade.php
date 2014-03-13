{{ Form::open(array(
  'action' => 'MOK\ImageUpload\ImageController@download',
  'id' => 'VImageUploaderForm',
  'role' => 'form',
  'files' => true,
  'class' => 'form-horizontal',
  'data-behavior' => 'hitTarget',
  'data-listener' => 'submit',
  'data-action' => 'click',
  'data-selector' => '#VimageUploader-url-button',
)) }}

<div class="row">

  <div class="form-group">
    <label class="col-md-3 control-label" for="VImageUploader-file-placeholder">Choose a file: </label>
    <div class="input-group col-md-8">
      {{ Form::text('', '', array(
        'id' => 'VImageUploader-file-placeholder',
        'class' => 'form-control file_placeholder',
        'data-behavior' => 'hitTarget',
        'data-listener' => 'focus',
        'data-action' => 'click',
        'data-selector' => '#VImageUploader-file',
      )) }}
      {{ Form::file('imageFile', array_merge(array(
      'class' => 'file_upload hidden',
      'id' => 'VImageUploader-file',
      'data-behavior' => 'loadImageFile',
      'data-action' => action('MOK\ImageUpload\ImageController@upload'),
      ), $formData)) }}
      <span class="input-group-btn">
        {{ Form::button('Upload', array(
          'class' => 'btn btn-info btn-md',
        'data-behavior' => 'hitTarget',
        'data-listener' => 'click',
        'data-action' => 'click',
        'data-selector' => '#VImageUploader-file',
        )) }}
      </span>
    </div>
  </div>

  <div class="form-group">
    <label class="col-md-3 control-label" for="VImageUploader-url">Paste a URL: </label>
    <div class="col-md-8 input-group">
      {{ Form::text('url', '', array(
        'id' => 'VImageUploader-url',
        'class' => 'form-control',
      )) }}
      <span class="input-group-btn">
        {{ Form::button('Download', array_merge(array(
          'id' => 'VimageUploader-url-button',
          'class' => 'btn btn-info btn-md',
          'data-behavior' => 'loadImageURL',
          'data-selector' => '#VImageUploader-url',
          'data-action' => action('MOK\ImageUpload\ImageController@download'),
        ), $formData)) }}
    </div>
  </div>


</div>

  <button type="submit" class="btn btn-success">Go!</button>

{{ Form::close() }}
