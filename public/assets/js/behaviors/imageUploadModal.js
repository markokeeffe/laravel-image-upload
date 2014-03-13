/**
 * Author:  Mark O'Keeffe

 * Date:    13/09/13
 *
 * [Laravel Workbench]
 */
V.Behaviors.imageUploadModal = function($modal){

  // Create a bootstrap modal instance for the main modal div
  $modal.modal({
    show: false,
    backdrop: 'static',
    keyboard: false
  });

  V.ImageUpload.modal({
    heading: 'Hello!',
    body: 'Here\'s some '
  });

  V.ImageUpload.modal = function(content, no_close){

    // Basic elements that make up the modal window
    var $content = $modal.find('.modal-content'),
      $header = $('<div class="modal-header"/>'),
      $body = $('<div class="modal-body"/>'),
      $footer = $('<div class="modal-footer"/>'),
      $heading = $('<div class="modal-title"/>'),
      $closeButtons = {
        header:  $('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>'),
        footer:  $('<button type="button" class="btn btn-default btn-danger" data-dismiss="modal">Close</button>')
      };

    // Set the title text and add the header elements
    $heading.text(content.heading);
    $header
      .append($closeButtons.header)
      .append($heading);

    // Any custom content to add to the footer?
    if (undefined !== content.footer) {
      $footer.html(content.footer);
    }

    // Add the close button to the footer
    $footer
      .append($closeButtons.footer);

    // Is the modal for a form?
    // We need to wrap the form around the body and footer,
    // moving the submit button into the footer
    if ($(content.body).prop('tagName') === 'FORM') {
      // Get the form element
      var $form = $(content.body),
      // Extract the submit button
        $submit = $form.find('*[type="submit"]').last().detach(),
      // Extract the form contents
        $formContents = $form.contents().detach();

      // Put contents inside the body container
      $body.append($formContents);
      // Put the submit button inside the footer
      $footer.append($submit);

      // Append all parts of modal into the form
      $form
        .append($header)
        .append($body)
        .append($footer);

      // Add the form to the content area
      $content.html($form);

    } else {

      // Set the content of the body
      $body.html(content.body);

      // Append all parts of the modal to the content area
      $content
        .html($header)
        .append($body)
        .append($footer);
    }

    $modal.on('hidden.bs.modal', function () {
      $modal.find('.modal-dialog').removeAttr('style');
      $content.empty();
      $(document).unbind('keydown');
    });

    $modal.on('show.bs.modal', function(){
      V.loadBehavior($content);
    });

    $modal.modal('show');


    if (no_close == true) {
      $closeButtons.header.hide();
      $closeButtons.footer.hide();
      $modal.addClass('no-close');
    } else {
      $closeButtons.header.show();
      $closeButtons.footer.show();
      $modal.removeClass('no-close');
      $(document).keydown(function(e){
        switch(e.keyCode){
          case 13 : // Enter
            e.preventDefault();
            if($('form', $modal).length){
              $('form', $modal).submit();
            }else{
              e.preventDefault();
              $('.modal-button', $modal).trigger('click');
            }
            break;
          case 27 : // ESC
            e.preventDefault();
            $modal.modal('hide');
            break;
        }
      });
    }

  };

  /**
   * Close the modal window
   */
  V.ImageUpload.closeModal = function(){
    $modal.modal('hide');
  };

};
