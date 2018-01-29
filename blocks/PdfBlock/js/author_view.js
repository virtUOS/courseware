import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        var $view = this;
        $view.$('.cw-pdf-set-file option[value="'+$view.$('.cw-pdf-file-stored').val()+'"]').prop('selected', true);
        return this;
    },

    onNavigate(event) {
        if (!$('section .block-content button[name=save]').length) {
            return;
        }
        if(event.isUserInputHandled) {
            return;
        }
        event.isUserInputHandled = true;
        Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
    },

    onModeSwitch(toView, event) {
        if (toView != 'student') {
            return;
        }
        // the user already switched back (i.e. the is not visible)
        if (!this.$el.is(':visible')) {
            return;
        }
        // another listener already handled the user's feedback
        if (event.isUserInputHandled) {
            return;
        }
        event.isUserInputHandled = true;
        Backbone.trigger('preventviewswitch', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie trotzdem fortfahren?'));
    },

    onSave(event) {
        var $view = this;
        var $pdf_file     = $view.$('.cw-pdf-set-file').val();
        var $pdf_file_id  = $view.$('.cw-pdf-set-file option:selected').attr('document_id');
        var $pdf_filename = $view.$('.cw-pdf-set-file option:selected').attr('filename');
        var $pdf_title    = $view.$('.cw-pdf-set-title').val();
        if ($pdf_title == "") {
            $pdf_title = $pdf_filename;
        }
        helper
            .callHandler(this.model.id, 'save', {
                pdf_file: $pdf_file,
                pdf_filename: $pdf_filename,
                pdf_file_id: $pdf_file_id,
                pdf_title: $pdf_title
            })
            .then(
                // success
                function () {
                  $(event.target).addClass('accept');
                  $view.switchBack();
                  // reload if PDFJS has not been loaded jet
                  if (typeof PDFJS === 'undefined') {
                    helper.reload();
                  }
                },
                // error
                function (error) {
                  var errorMessage = 'Could not update the block: '+$.parseJSON(error.responseText).reason;
                  alert(errorMessage);
                  console.log(errorMessage, arguments);
                }
            );
      }
});
