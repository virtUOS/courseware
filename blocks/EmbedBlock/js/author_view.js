import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({
    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'change select.cw-embedblock-source': 'selectPlatform',
        'change input.cw-embedblock-url': 'checkURL',
        'keyup input.cw-embedblock-url': 'checkURL'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        var $embed_source = this.$('.cw-embedblock-source-stored').val();
        this.$('.cw-embedblock-source option[value="'+$embed_source+'"]').prop('selected', true);
        this.selectPlatform();
        this.checkURL();
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
        var $embed_source = this.$('select.cw-embedblock-source option:selected').val();
        this.$('.cw-embedblock-link li').hide();
        this.$('.cw-embedblock-link li[value="'+$embed_source+'"]').show();
        this.checkURL();

    },

    checkURL() {
        var url_input = this.$('.cw-embedblock-url');
        var url = url_input.val();
        var $embed_source = this.$('select.cw-embedblock-source option:selected').val();
        if (url != '') {
            if(url.includes($embed_source)) {
                url_input.removeClass('cw-embedblock-wrong-plattform');
                this.$('.cw-embedblock-url-info-wrong-plattform').hide();
            } else {
                url_input.addClass('cw-embedblock-wrong-plattform');
                this.$('.cw-embedblock-url-info-wrong-plattform').show();
            }
        } else {
            url_input.removeClass('cw-embedblock-wrong-plattform');
            this.$('.cw-embedblock-url-info-wrong-plattform').hide();
        }
    }
});
