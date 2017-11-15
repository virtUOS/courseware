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
    var $section = this.$el.closest('section.HtmlBlock');
    var $sortingButtons = jQuery('button.lower', $section);
    $sortingButtons = $sortingButtons.add(jQuery('button.raise', $section));
    $sortingButtons.addClass('no-sorting');

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

  postRender() {
    this.$('textarea').addToolbar();
  },

  // not used yet
  render() {
    return this;
  },

  onSave(event) {
    var textarea = this.$('textarea'),
        new_val = textarea.val(),
        view = this;

    helper
      .callHandler(this.model.id, 'save', { content: new_val })
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
