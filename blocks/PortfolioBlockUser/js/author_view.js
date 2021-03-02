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
        var $section = this.$el.closest('section.PortfolioBlockUser');
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
    var view = this;
    var $content = '';
    var $textarea = this.$('textarea');
    var wysiwyg_editor = CKEDITOR.instances[$textarea.get(0).id]; 

    wysiwyg_editor.setData(STUDIP.wysiwyg.markAsHtml(wysiwyg_editor.getData())); 
    wysiwyg_editor.updateElement();
    $content = $textarea.val();

    helper
      .callHandler(this.model.id, 'save', { content: $content })
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
