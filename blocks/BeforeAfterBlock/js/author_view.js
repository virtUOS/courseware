import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'change select.cw-ba-source': 'selectSource'
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
        var $source = $view.$('.cw-ba-stored-source').val();
        $view.$('.cw-ba-wrapper').hide();
        $view.$('select.cw-ba-source option[value="'+$source+'"]').prop('selected', true);

        switch ($source) {
            case 'url':
            default:
                $view.$('.cw-ba-wrapper-url').show();
                break;
            case 'cw':
                var $files = $view.$('.cw-ba-stored-files').val();
                if ($files != ''){
                    $files = JSON.parse($files);
                    $view.$('select.cw-ba-file-before option[file-id="'+$files.before.file_id+'"]').prop('selected', true);
                    $view.$('select.cw-ba-file-after option[file-id="'+$files.after.file_id+'"]').prop('selected', true);
                }
                $view.$('.cw-ba-wrapper-files').show();
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
        var $ba_source = $view.$('.cw-ba-source').val();
        var $ba_url = '';
        var $ba_files = '';
        switch ($ba_source) {
            case 'url':
                $ba_url = {};
                $ba_url.before = {};
                $ba_url.after = {};
                $ba_url.before.url = $view.$('.cw-ba-url-before').val();
                $ba_url.after.url = $view.$('.cw-ba-url-after').val();
                $ba_url = JSON.stringify($ba_url);
                break;
            case 'cw':
                $ba_files = {};
                $ba_files.before = {};
                $ba_files.after = {};
                $ba_files.before.file_id = $view.$('.cw-ba-file-before option:selected').attr('file-id');
                $ba_files.before.file_name = $view.$('.cw-ba-file-before option:selected').attr('filename');
                $ba_files.after.file_id = $view.$('.cw-ba-file-after option:selected').attr('file-id');
                $ba_files.after.file_name = $view.$('.cw-ba-file-after option:selected').attr('filename');
                $ba_files = JSON.stringify($ba_files);
                break;
        }

        helper
        .callHandler(this.model.id, 'save', {
              ba_source : $ba_source,
              ba_url : $ba_url,
              ba_files : $ba_files
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

    selectSource() {
        var $view = this;
        var $selection = $view.$('.cw-ba-source').val();
        switch($selection) {
            case 'cw':
                $view.$('.cw-ba-wrapper').hide();
                $view.$('.cw-ba-wrapper-files').show();
                break;
            case 'url':
                $view.$('.cw-ba-wrapper').hide();
                $view.$('.cw-ba-wrapper-url').show();
                break;
        }
        return;
    }
});
