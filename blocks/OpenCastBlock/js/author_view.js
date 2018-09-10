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
        var $view = this;
        var $opencastid = $view.$('.cw-opencast-stored-id').val();
        if ($opencastid != '') {
            $view.$('.cw-opencast-content option[data-opencastid="'+$opencastid+'"]').prop('selected', true);
        }
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
        var $opencast_content = {};
        $opencast_content.url_mp4 = $view.$(".cw-opencast-content option:selected").attr('data-urlmp4');
        $opencast_content.url_opencast = $view.$(".cw-opencast-content option:selected").attr('data-urlopencast');
        $opencast_content.id = $view.$(".cw-opencast-content option:selected").attr('data-opencastid');
        $opencast_content.useplayer = $view.$('.cw-opencast-useocplayer').prop( "checked" );
        $opencast_content = JSON.stringify($opencast_content);
        helper
        .callHandler(this.model.id, 'save', {
              opencast_content : $opencast_content
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
