/**
 * Author:  Mark O'Keeffe

 * Date:    24/09/13
 *
 * [Laravel Workbench]
 */

/**
 * Load the image data using the FileReader API and display it for cropping
 */
V.Functions.loadImageFile = function(input){

  // Get the file object
  var $input = $(input),
    file = input.files[0],
    reader,
    source,
    $img;

  // Check the MIME type of the file to ensure it is an image
  if(!file.type.match(/image\.*/)){
    this.resetForm();
    alert('Invalid file type. Please upload image files only.');
    return;
  }

  // Read the file data and show a preview of the image for cropping
  if(window.FileReader){
    reader = new FileReader();
    reader.onloadend = function (e) {

      source = e.target.result;
      $img = $('<img/>', {
        src: source,
        alt: 'Set Crop',
        "data-behavior": 'crop resizeModal',
        "data-action": $(input).data('action')
      });

      V.ImageUpload.modal({
        heading: 'Choose Crop',
        body: $img
      });

    };
    // Create URL data from the file for uploading
    reader.readAsDataURL(file);
  }

  // Create a FormData object to add the file upload to
  V.ImageUpload.formdata = false;
  if(window.FormData){
    V.ImageUpload.formdata = new FormData();
    V.ImageUpload.formdata.append('image', file);

    // Set the 'imageable_type' (the class that owns the image)
    V.ImageUpload.formdata.append('imageableType', $input.data('imageableType'));

    // If provided, set the 'imageable_id' (The ID of the image owner)
    if (undefined !== $input.data('imageableId')) {
      V.ImageUpload.formdata.append('imageableId', $input.data('imageableId'));
    }
    if (undefined !== V.ImageUpload.params.imageId) {
      V.ImageUpload.formdata.append('imageId', V.ImageUpload.params.imageId);
    }
    if (undefined !== V.ImageUpload.params.size) {
      V.ImageUpload.formdata.append('size', V.ImageUpload.params.size);
    }
  }
};

/**
 * Download an image from a URL to crop and resize
 *
 * @param $button
 * @param $input
 */
V.Functions.loadImageURL = function($button, $input){

  // Get the URL from the text input
  var url = $input.val(),
    $img;

  // Set up the formdata object for the AJAX submit of the crop later
  V.ImageUpload.formdata = {
    url: url,
    imageableType: $button.data('imageableType')
  };

  if (undefined !== $button.data('imageableId')) {
    V.ImageUpload.formdata.imageableId = $button.data('imageableId');
  }
  if (undefined !== V.ImageUpload.params.imageId) {
    V.ImageUpload.formdata.imageId = V.ImageUpload.params.imageId;
  }
  if (undefined !== V.ImageUpload.params.size) {
    V.ImageUpload.formdata.size = V.ImageUpload.params.size;
  }

  // Attempt to load an image from the given URL
  $img = $('<img/>', {
    src: url,
    alt: 'Set Crop',
    "data-behavior": 'crop resizeModal',
    "data-action": $button.data('action'),
    load: function(){
      // Image has successfully loaded, open a modal with
      // a clone of the image for cropping
      V.ImageUpload.modal({
        heading: 'Choose Crop',
        body: $img.clone()
      });
    },
    error: function(){
      // The URL has not loaded a valid image, show an error
      V.ImageUpload.modal({
        heading: 'Invalid Image',
        body: '<strong>You have attempted to download an invalid image. Please try again.</strong>'
      });
    }
  });

};

/**
 * Save the selected crop dimensions by POSTing the
 * image data and crop co-ordinates to the server
 */
V.Functions.saveCrop = function($image) {

  console.log($image.data('action'));

  // Set up some AJAX request settings
  var settings = {
    url: $image.data('action'),
    success: function(rtn) {
      if (rtn.type === 'modal') {
        V.ImageUpload.modal(rtn.msg);
      } else if (rtn.type === 'success') {
        V.ImageUpload.closeModal();
        V.Functions.complete(rtn.msg);
      }
    }
  };

  // Is 'formdata' a normal object?
  if ($.isPlainObject(V.ImageUpload.formdata)) {
    // Add the crop dimensions
    V.ImageUpload.formdata.crop = V.crop;
    // Add the data to the AJAX settings
    settings.data = V.ImageUpload.formdata;
  } else {
    // Add the crop dimensions
    $.each(V.crop, function(i,elem){
      V.ImageUpload.formdata.append('crop['+i+']',elem);
    });
    // Add the data to the AJAX settings
    settings.data = V.ImageUpload.formdata;

    // Set some extra settings to use the FormData object
    settings.processData = false;  // tell jQuery not to process the data
    settings.contentType = false;  // tell jQuery not to set contentType
  }

  $.ajax(settings);

};

/**
 * When the image loads for cropping, a 'Save Crop' button needs to be added
 *
 * @param $image
 */
V.Functions.addCropButton = function($image){
  // Create a button that can save the crop
  var $button = $('<button/>', {
    "class": 'btn btn-success'
  }).text('Save Crop');

  // Add the button to the modal
  $image.closest('.modal-body').prepend($button);

  $button.on('click', function(){
    V.Functions.saveCrop($image);
  });
};

// Action to perform when cropping region is selected
V.Functions.setCrop = function(c){
  V.crop = c;
};

/**
 * Save the focal point co-ordinates for a recently cropped image
 */
V.Functions.saveFocalPoint = function($image){
  $.ajax({
    url: $image.data('action'),
    data: {
      focalPoint: V.ImageUpload.params.focalPoint
    },
    success: function(rtn){

      // The focal point is saved
      if (rtn.type == 'success') {

        V.Functions.complete(rtn.msg);

      }

      // Close the image uploader
      V.ImageUpload.closeModal();

    }
  });
};

/**
 * Function to perform when an image has been uploaded, cropped & saved
 *
 * Update the form where the image upload was initiated
 *
 * @param rtn
 */
V.Functions.complete = function(rtn){

  // Update the form field for the image ID if there is one
  if (undefined !== V.ImageUpload.params.hiddenSelector) {
    var $hiddenField = $(V.ImageUpload.params.hiddenSelector);
    $hiddenField.val(rtn.id);
  }

  // Has a display container been specified to add the newly uploaded image to?
  if (undefined !== V.ImageUpload.params.displayContainer) {

    var $uploadedImg = $('<img/>', {
      src: rtn.src
    });

    if (undefined !== V.ImageUpload.params.displaySize) {
      var size = V.ImageUpload.params.displaySize.split('x');
      $uploadedImg.css({
        width: size[0],
        height: size[1]
      });
    }

    $(V.ImageUpload.params.displayContainer).append($uploadedImg);
  }

  // Are we uploading over an existing image?
  if (undefined !== V.ImageUpload.params.imageSelector) {
    // Update the original link's src
    $(V.ImageUpload.params.imageSelector).attr('src', rtn.src);
  }

  // Has an alert been specified?
  if (undefined !== V.ImageUpload.params.alert
    && undefined !== V.ImageUpload.params.alertContainer
    && undefined !== V.ImageUpload.params.alertClass) {
    // Create the alert
    var $alert = $('<div/>', {
      "class": V.ImageUpload.params.alertClass
    }).html(V.ImageUpload.params.alert);
    // Add the alert to the alert container
    $(V.ImageUpload.params.alertContainer).html($alert);
  }

};
