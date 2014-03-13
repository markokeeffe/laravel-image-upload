/**
 * Author:  Mark O'Keeffe

 * Date:    13/09/13
 *
 * [Laravel Workbench]
 */
V.Behaviors.crop = function($image){

  // When the image has finished loading
  $image.off().on('load', function() {

    // Add the 'Save Crop' button to the modal
    V.Functions.addCropButton($image);

    // Initialize Jcrop on the image
    V.Jcrop = $.Jcrop($image);

    var opts = {
      onSelect: V.Functions.setCrop
    };

    // Has a specific image size been specified?
    if (undefined !== V.ImageUpload.params.size) {
      // Get the width and height values to lock the aspect ratio
      var sizes = V.ImageUpload.params.size.split('x');
      opts.aspectRatio = (sizes[0] / sizes[1]);
    }

    // Set the Jcrop options
    V.Jcrop.setOptions(opts);

    // Set the Jcrop selected area
    V.Jcrop.setSelect([
      0,
      0,
      $image[0].width,
      $image[0].height
    ]);

    // Set the dimensions of the selected crop area in the 'item'
    V.Functions.setCrop(V.Jcrop.tellSelect());

  });

};
