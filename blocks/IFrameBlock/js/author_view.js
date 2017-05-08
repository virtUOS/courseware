import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

  events: {
    'click button[name=save]':   'onSave',
    'click button[name=cancel]': 'switchBack',
    'click .submit-user-id-switch': 'toggleSubmitUserId'
  },

  initialize() {
    Backbone.on('beforemodeswitch', this.onModeSwitch, this);
    Backbone.on('beforenavigate', this.onNavigate, this);
  },

  render() {
    return this;
  },

  postRender() {
    this.toggleSubmitUserId();
  },

  onNavigate(event) {
    if (!$('section .block-content button[name=save]').length) {
      return;
    }

    if (event.isUserInputHandled) {
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

  toggleSubmitUserId() {
    var state = this.$('.submit-user-id-switch').is( ':checked');
    if (state) this.$('.iframe-submit-user').show();
    else this.$('.iframe-submit-user').hide();
  },

  onSave(event) {
    var view            = this;
    var $url            = view.$('input.urlinput').val();
    var $height         = view.$('input.heightinput').val();
    var $width          = view.$('input.widthinput').val();
    var $salt           = view.$('.salt').val();
    var $submit_param   = view.$('.submit-param').val();
    var $submit_user_id = view.$('.submit-user-id-switch').is( ':checked');

    helper
      .callHandler(this.model.id, 'save', {
        url: $url,
        height: $height,
        width: $width,
        salt: $salt, 
        submit_param: $submit_param, 
        submit_user_id: $submit_user_id })
      .then(function () {
        $(event.target).addClass('accept');
        view.switchBack();
      }).catch(function (error) {
        var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
        alert(errorMessage);
        console.log(errorMessage, arguments);
      });
  }
});
