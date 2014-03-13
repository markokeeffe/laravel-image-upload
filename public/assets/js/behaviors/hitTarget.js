/**
 * Author:  Mark O'Keeffe

 * Date:    13/09/13
 *
 * [Laravel Workbench]
 */
V.Behaviors.hitTarget = function($elem) {

  var $target = $($elem.data('selector')),
      listener = $elem.data('listener'),
      action = $elem.data('action');

  $elem.off().on(listener, function(){
    $target[action]();
    return false;
  });

};
