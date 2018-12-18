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

    render() {
        return this;
    },

    postRender() {
        var typewriter  = this.$('.cw-typewriter-stored-json').val();
        if (typewriter == '') {
            return;
        }
        typewriter = JSON.parse(typewriter);
        this.$('.cw-typewriter-content').val(typewriter.content);
        this.$('.cw-typewriter-speed').find('option[value="'+typewriter.speed+'"]').prop('selected', true);
        this.$('.cw-typewriter-font').find('option[value="'+typewriter.font+'"]').prop('selected', true);
        this.$('.cw-typewriter-font').find('option[value="'+typewriter.size+'"]').prop('selected', true);

        return this;
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
        var typewriter = {};
        typewriter.content = $view.$('.cw-typewriter-content').val();
        typewriter.speed = $view.$('.cw-typewriter-speed').val();
        typewriter.font = $view.$('.cw-typewriter-font').val();
        typewriter.size = $view.$('.cw-typewriter-size').val();
        typewriter = JSON.stringify(typewriter);

        helper
        .callHandler(this.model.id, 'save', {
            'typewriter_json' : typewriter,
        })
        .then(
            // success
            function () {
                $(event.target).addClass('accept');
                $view.switchBack();
            },

            // error
            function (error) {
                console.log(error);
            }
        );
    }
});
