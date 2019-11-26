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
    var $stored_folder = this.$('.gallery-stored-folder').val();
    this.$('.gallery-folder').select2({
      templateResult: state => {
        if (!state.id) { return state.text; }
        var $state = $(
          '<span class="' + state.element.className + '"></span><span>' + state.text + '</span>'
        );
        return $state;
      }
    });
    this.$('.gallery-folder').val($stored_folder).trigger('change');

    var $stored_autoplay = this.$('.gallery-stored-autoplay').val();
    var $stored_hidenav = this.$('.gallery-stored-hidenav').val();
    var $stored_show_names = this.$('.gallery-stored-show-names').val();

    if ($stored_autoplay == 1) {
      this.$('input[name="gallery-autoplay"]').prop( 'checked', true);
    }
    if ($stored_hidenav == 1) {
      this.$('input[name="gallery-hidenav"]').prop( 'checked', true);
    }
    if ($stored_show_names == 1) {
      this.$('input[name="gallery-show-names"]').prop( 'checked', true);
    }
  },

  onSave(event) {
    var view = this;
    var $autoplay = this.$('input[name="gallery-autoplay"]').prop( 'checked') ? 1 : 0;
    var $autoplaytimer = this.$('input[name="gallery-autoplay-timer"]').val();
    var $height = this.$('input[name="gallery-height"]').val();
    var $hidenav = this.$('input[name="gallery-hidenav"]').prop( 'checked') ? 1 : 0;
    var $showNames = this.$('input[name="gallery-show-names"]').prop( 'checked') ? 1 : 0;
    var $gallery_folder_id = this.$('.gallery-folder').val();
    helper
      .callHandler(this.model.id, 'save', {
        gallery_folder_id: $gallery_folder_id,
        gallery_height: $height,
        gallery_autoplay: $autoplay,
        gallery_autoplay_timer: $autoplaytimer,
        gallery_hidenav: $hidenav,
        gallery_show_names: $showNames
      })
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
