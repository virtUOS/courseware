import $ from 'jquery'
import Backbone from 'backbone'
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

  initializeFromDOM() {
    var $section = this.$el.closest('section.ForumBlock');
    var $sortingButtons = $('button.lower', $section);
    $sortingButtons = $sortingButtons.add($('button.raise', $section));
    $sortingButtons.addClass('no-sorting');
  },

  onNavigate(event){
    if (!$('section .block-content button[name=save]').length) {
      return;
    }
    if (event.isUserInputHandled) {
      return;
    }
    event.isUserInputHandled = true;
    Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
  },

  onSave(event) {
    var area = this.$('select[name=area]').val(),
        view = this;

    helper
      .callHandler(this.model.id, 'save', { area_id: area })
      .then(function () {
        $(event.target).addClass('accept');
        view.switchBack();
      }).catch(function (error) {
        var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
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
