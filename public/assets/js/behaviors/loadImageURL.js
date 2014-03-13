/**
 * Author:  Mark O'Keeffe

 * Date:    25/09/13
 *
 * [Laravel Workbench]
 */
/**
 * Author:  Mark O'Keeffe

 * Date:    13/09/13
 *
 * [Laravel Workbench]
 */
V.Behaviors.loadImageURL = function($button){

  var $input = $($button.data('selector'));

  $button.off().on('click', function(){
    V.Functions.loadImageURL($button, $input);
  });

};
