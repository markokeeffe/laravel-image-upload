/**
 * Author:  Mark O'Keeffe

 * Date:    13/09/13
 *
 * [Laravel Workbench]
 */
V.Behaviors.loadImageFile = function($input){

  // When the file input changes, render the image data for cropping
  $input.off().on('change', function(){
    V.Functions.loadImageFile(this);
  });

};
