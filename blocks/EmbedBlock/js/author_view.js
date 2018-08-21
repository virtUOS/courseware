import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({
    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'change select.cw-embedblock-source': 'selectPlatform'
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
        var $embed_source = $view.$('.cw-embedblock-source-stored').val();
        $view.$('.cw-embedblock-source option[value="'+$embed_source+'"]').prop('selected', true);
        $view.selectPlatform();
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
        var $embed_url = $view.$('.cw-embedblock-url').val();
        var $embed_source = $view.$('select.cw-embedblock-source option:selected').val();
        helper
          .callHandler(this.model.id, 'save', {
            embed_url: $embed_url,
            embed_source: $embed_source
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

    selectPlatform() {
        var $view = this;
        var $embed_source = $view.$('select.cw-embedblock-source option:selected').val();
        $view.$('.cw-embedblock-link li').hide();
        $view.$('.cw-embedblock-link li[value="'+$embed_source+'"]').show();
    }
});
