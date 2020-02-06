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
        let $opencast_id = this.$('.cw-opencast-stored-id').val();
        let $opencast_player = this.$('.cw-opencast-stored-player').val();
        if ($opencast_id != '') {
            this.$('.cw-opencast-content option[data-opencastid="'+$opencast_id+'"]').prop('selected', true);
        }
        switch($opencast_player){
            case 'treu':
            case 'theodul':
            default:
                this.$('.cw-opencast-useocplayer[value="theodul"]').prop('checked', true);
                break;
            case 'paella':
                this.$('.cw-opencast-useocplayer[value="paella"]').prop('checked', true);
                break;
            case 'false':
                this.$('.cw-opencast-useocplayer[value="false"]').prop('checked', true);
                break;
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
        let $opencast_content = {};
        $opencast_content.id = this.$(".cw-opencast-content option:selected").attr('data-opencastid');
        $opencast_content.useplayer = this.$('.cw-opencast-useocplayer:checked').val();
        $opencast_content.title = this.$(".cw-opencast-content option:selected").attr('data-episodetitle');

        switch($opencast_content.useplayer) {
            case 'theodul':
            default:
                $opencast_content.url_opencast_theodul = this.$(".cw-opencast-content option:selected").attr('data-urlopencasttheodul');
                break;
            case 'paella':
                $opencast_content.url_opencast_paella = this.$(".cw-opencast-content option:selected").attr('data-urlopencastpaella');
                break;
            case 'false':
                $opencast_content.url_mp4 = this.$(".cw-opencast-content option:selected").attr('data-urlmp4');
                break;
        }
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
