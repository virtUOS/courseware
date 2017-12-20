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
        var $view = this;
        var $folders = $view.$el.find('.gallery-folder option');
        var $stored_folder = $view.$el.find('.gallery-stored-folder').val();
        $folders.each(function () {
          if($(this).attr('folder_id') == $stored_folder) {
            $(this).prop('selected', true);
          }
        });
        var $stored_autoplay = $view.$el.find('.gallery-stored-autoplay').val();
        var $stored_hidenav = $view.$el.find('.gallery-stored-hidenav').val();

        if ($stored_autoplay == 1) {
            $view.$el.find('input[name="gallery-autoplay"]').prop( "checked", true)
        }
        if ($stored_hidenav == 1) {
            $view.$el.find('input[name="gallery-hidenav"]').prop( "checked", true)
        }
    },

    onSave(event) {
        var view = this;
        var $folder = this.$el.find('.gallery-folder');
        var $autoplay = this.$el.find('input[name="gallery-autoplay"]').prop( "checked") ? 1 : 0;
        var $autoplaytimer = this.$el.find('input[name="gallery-autoplay-timer"]').val();
        var $height = this.$el.find('input[name="gallery-height"]').val();
        var $hidenav = this.$el.find('input[name="gallery-hidenav"]').prop( "checked") ? 1 : 0;
        var $gallery_folder_id = $folder.find('option:selected').attr('folder_id');
        helper
          .callHandler(this.model.id, 'save', {gallery_folder_id: $gallery_folder_id, gallery_height: $height, gallery_autoplay: $autoplay, gallery_autoplay_timer: $autoplaytimer, gallery_hidenav: $hidenav})
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
