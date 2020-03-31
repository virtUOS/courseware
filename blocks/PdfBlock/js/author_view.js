import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'onCancel',
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        this.$('.cw-pdf-set-file').select2({
            templateResult: state => {
              if (!state.id) { return state.text; }
              var $state = $(
                '<span data-filename="' + state.element.dataset.filename +'">' + state.text + '</span>'
              );
              return $state;
            }
        });
        let storedFile = this.$('.cw-pdf-file-id-stored').val();
        if (storedFile != '') {
            this.$('.cw-pdf-set-file').val(storedFile).trigger('change');
        }

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
        var view = this;
        var $pdf_file_id = this.$('.cw-pdf-set-file').val();
        var $pdf_filename = this.$('.cw-pdf-set-file').find(':selected').data('filename');
        var $pdf_title = this.$('.cw-pdf-set-title').val();
        var $pdf_disable_download = this.$('.cw-pdf-disable-download').is(':checked');
        if ($pdf_title == "") {
            $pdf_title = $pdf_filename;
        }
        helper
            .callHandler(this.model.id, 'save', {
                pdf_filename: $pdf_filename,
                pdf_file_id: $pdf_file_id,
                pdf_title: $pdf_title,
                pdf_disable_download: $pdf_disable_download
            })
            .then(
                // success
                function () {
                  $(event.target).addClass('accept');
                  view.switchBack();
                  helper.reload();
                },
                // error
                function (error) {
                  var errorMessage = 'Could not update the block: '+$.parseJSON(error.responseText).reason;
                  alert(errorMessage);
                  console.log(errorMessage, arguments);
                }
            );
    },

    onCancel() {
        this.switchBack();
        helper.reload();
    }
});
