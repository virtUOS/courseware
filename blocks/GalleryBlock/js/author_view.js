import Backbone from 'backbone'
import jQuery from 'jquery'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    onNavigate(event) {
        if (!jQuery('section .block-content button[name=save]').length) {
            return;
        }
        if (event.isUserInputHandled) {
            return;
        }
        event.isUserInputHandled = true;
        Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
    },

    render() {
        return this;
    },

    postRender() {

    },
    
    onSave(event) {
        var view = this;
        var $gallery_content = "";
        var $folder = this.$el.find('.gallery-folder');
        var $gallery_folder_id = $folder.find('option:selected').attr('folder_id');
        
        helper
          .callHandler(this.model.id, 'save', { gallery_content: $gallery_content, gallery_folder_id: $gallery_folder_id})
          .then(function () {
            jQuery(event.target).addClass('accept');
            view.switchBack();
          }).catch(function (error) {
            var errorMessage = 'Could not update the block: ' + jQuery.parseJSON(error.responseText).reason;
            alert(errorMessage);
            console.log(errorMessage, arguments);
          });
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
    }
});
