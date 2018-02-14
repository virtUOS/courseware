import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

  events: {
    'click button[name=save]':   'onSave',
    'click button[name=cancel]': 'switchBack',
    'change select.cw-audioblock-source': 'selectSource'
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
    $view.$('.cw-audioblock-description').val($view.$('.cw-audioblock-description-stored').val());
    $view.$('select.cw-audioblock-source option[value="'+$view.$('.cw-audioblock-source-stored').val()+'"]').prop('selected', true);

    if ($view.$('.cw-audioblock-source-stored').val() == '') {
      $view.$('input.cw-audioblock-file').hide();
      $view.$('select.cw-audioblock-file').show();
      $view.$('.cw-audioblock-source option[value="cw"]').prop('selected', true);
    } else if ($view.$('.cw-audioblock-source-stored').val() == 'cw') {
      $view.$('input.cw-audioblock-file').hide();
      $view.$('.cw-audioblock-file option[value="'+$view.$('.cw-audioblock-file-stored').val()+'"]').prop('selected', true);
    } else {
      $view.$('select.cw-audioblock-file').hide();
      $view.$('input.cw-audioblock-file').val($view.$('.cw-audioblock-file-stored').val());
    }
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
    var $audiodescription = $view.$('.cw-audioblock-description').val();
    var $audiosource = $view.$('.cw-audioblock-source').val();
    var $audiofile, $audioid, $audio_file_name;
    if ($audiosource == 'cw') {
      $audiofile = $view.$('select.cw-audioblock-file').val();
      $audioid = $view.$('select.cw-audioblock-file option:selected').attr('file-id');
      $audio_file_name = $view.$('select.cw-audioblock-file option:selected').attr('filename');
    } else {
      $audiofile = $view.$('input.cw-audioblock-file').val();
      $audioid = '';
    }

    helper
      .callHandler(this.model.id, 'save', {
        audio_file: $audiofile,
        audio_file_name: $audio_file_name,
        audio_id: $audioid,
        audio_description: $audiodescription,
        audio_source: $audiosource
      })
      .then(
        // success
        function () {
          $(event.target).addClass('accept');
          $view.switchBack();
        },

        // error
        function (error) {
          var errorMessage = 'Could not update the block: '+$.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
        });
  },

  selectSource() {
    var $view = this;
    var $selection = $view.$('.cw-audioblock-source').val();
    if ($selection == 'cw') {
      $view.$('input.cw-audioblock-file').hide();
      $view.$('select.cw-audioblock-file').show();
      $view.$('label[for="cw-audioblock-file"]').html('Audiodatei:');
    } else {
      $view.$('select.cw-audioblock-file').hide();
      $view.$('input.cw-audioblock-file').show();
      $view.$('label[for="cw-audioblock-file"]').html('URL:');
    }
    return;
  }
});
