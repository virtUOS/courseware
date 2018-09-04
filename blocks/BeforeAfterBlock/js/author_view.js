import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({
    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'change select.cw-ba-source-before': 'selectSource',
        'change select.cw-ba-source-after': 'selectSource'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    render() {
        return this;
    },

    postRender() {
        if (this.$('.cw-ba-source-before').val() == 'url') {
            this.$('.cw-ba-source-before-url').show();
            this.$('.cw-ba-source-before-url-info').show();
            this.$('.cw-ba-source-before-file').hide();
            this.$('.cw-ba-source-before-file-info').hide();
        } else {
            let img_before = this.$('.cw-ba-stored-file-id-before').val();
            let select_before = this.$('.cw-ba-source-before-file');
            this.$('.cw-ba-source-before-url').hide();
            select_before.show();
            select_before.find('option[file-id="'+img_before+'"]').prop('selected', true);
        }

        if (this.$('.cw-ba-source-after').val() == 'url') {
            this.$('.cw-ba-source-after-url').show();
            this.$('.cw-ba-source-after-url-info').show();
            this.$('.cw-ba-source-after-file').hide();
            this.$('.cw-ba-source-after-file-info').hide();
        } else {
            let img_after = this.$('.cw-ba-stored-file-id-after').val();
            let select_after = this.$('.cw-ba-source-after-file');
            this.$('.cw-ba-source-after-url').hide();
            this.$('.cw-ba-source-after-url-info').hide();
            this.$('.cw-ba-source-after-file-info').show();
            select_after.show();
            select_after.find('option[file-id="'+img_after+'"]').prop('selected', true);
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
        var $before = {};
        var $after = {};

        $before.source = this.$('.cw-ba-source-before').val();
        if ($before.source == 'url') {
            $before.url = this.$('.cw-ba-source-before-url').val();
        } else {
            $before.url = this.$('.cw-ba-source-before-file option:selected').attr('file-url');
            $before.file_id = this.$('.cw-ba-source-before-file option:selected').attr('file-id');
            $before.file_name = this.$('.cw-ba-source-before-file option:selected').attr('filename');
        }

        $after.source = this.$('.cw-ba-source-after').val();
        if ($after.source == 'url') {
            $after.url = this.$('.cw-ba-source-after-url').val();
        } else {
            $after.url = this.$('.cw-ba-source-after-file option:selected').attr('file-url');
            $after.file_id = this.$('.cw-ba-source-after-file option:selected').attr('file-id');
            $after.file_name = this.$('.cw-ba-source-after-file option:selected').attr('filename');
        }

        $before = JSON.stringify($before);
        $after = JSON.stringify($after);

        helper
        .callHandler(this.model.id, 'save', {
              ba_before : $before,
              ba_after : $after
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
    },

    selectSource(event) {
        var type = '';
        
        if ($(event.currentTarget).hasClass('cw-ba-source-before')) {
            type = 'before';
        }
        if ($(event.currentTarget).hasClass('cw-ba-source-after')) {
            type = 'after';
        }

        if (type == '') { return;}
        if ($(event.currentTarget).val() == 'url') {
            this.$('.cw-ba-source-'+type+'-url').show();
            this.$('.cw-ba-source-'+type+'-url-info').show();
            this.$('.cw-ba-source-'+type+'-file').hide();
            this.$('.cw-ba-source-'+type+'-file-info').hide();
        } 
        if ($(event.currentTarget).val() == 'file') {
            this.$('.cw-ba-source-'+type+'-file').show();
            this.$('.cw-ba-source-'+type+'-file-info').show();
            this.$('.cw-ba-source-'+type+'-url').hide();
            this.$('.cw-ba-source-'+type+'-url-info').hide();
        }

        return;
    }
});
