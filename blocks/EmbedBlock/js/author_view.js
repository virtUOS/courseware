import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({
    events: {
        'click button[name=save]':   'onSave',
        'click button[name=cancel]': 'switchBack',
        'change select.cw-embedblock-source': 'selectPlatform',
        'change input.cw-embedblock-url': 'selectCorrectPlatform',
        'keyup input.cw-embedblock-url': 'selectCorrectPlatform',
        'change input.cw-embedblock-time-start-check': 'toggleTime',
        'change input.cw-embedblock-time-end-check': 'toggleTime',
        'change .cw-embedblock-time input[type="number"]' : 'timeValidator'
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
        if (this.$('.cw-embedblock-fullwidth-stored').val() == 1) {
            this.$('input[name="cw-embedblock-fullwidth"]').prop('checked', true);
        }
        this.selectPlatform();
        this.checkURL();
        this.setTime();
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
        var $embed_title = $view.$('input[name="cw-embedblock-title"]').val();
        var $embed_url = $view.$('.cw-embedblock-url').val();
        var $embed_source = $view.$('select.cw-embedblock-source option:selected').val();
        var use_time = this.$('.cw-embedblock-time-start-check').prop('checked');
        var $embed_time = {};
        var $embed_fullwidth = false;
        if (use_time){
            $embed_time.start = parseInt (60 * this.$('.cw-embedblock-time-start-min').val()) + parseInt (this.$('.cw-embedblock-time-start-sec').val());
            $embed_time.end = parseInt(60 * this.$('.cw-embedblock-time-end-min').val()) + parseInt(this.$('.cw-embedblock-time-end-sec').val());
            if (($embed_time.start >= $embed_time.end) || (this.$('.cw-embedblock-time-end-check').prop( "checked" ) == false)){
                $embed_time.end = '';
            }
            $embed_time = JSON.stringify($embed_time);
        } else {
            $embed_time = null;
        }
        if ($view.isImage()) {
            $embed_fullwidth =  $view.$('input[name="cw-embedblock-fullwidth"]').prop('checked');
        }

        helper
          .callHandler(this.model.id, 'save', {
            embed_url: $embed_url,
            embed_source: $embed_source,
            embed_time: $embed_time,
            embed_title: $embed_title,
            embed_fullwidth: $embed_fullwidth
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
        if ($embed_source == 'youtube'){
            this.$('.cw-embedblock-time').show();
        } else {
            this.$('.cw-embedblock-time').hide();
        }
        switch ($embed_source) {
            case 'youtube':
                this.$('.cw-embedblock-time').show();
                break;
            case 'deviantart':
            case 'flickr':
            case 'giphy':
                this.$('.cw-embedblock-image-options').show();
                break;
            default:
                this.$('.cw-embedblock-time').hide();
                this.$('.cw-embedblock-image-options').hide();
        }
        this.checkURL();

    },

    checkURL() {
        var url_input = this.$('.cw-embedblock-url');
        var url = url_input.val();
        var $embed_source = this.$('select.cw-embedblock-source option:selected').val();
        if (url != '') {
            url_input.addClass('cw-embedblock-wrong-plattform');
            this.$('.cw-embedblock-url-info-wrong-plattform').show();

            if(
                (url.includes($embed_source)) && (!url.includes('sway')) 
                || (($embed_source == 'youtube')&&(url.includes('youtu.be'))) 
                || (($embed_source == 'sway.office')&&(url.includes('sway.office')))
                || (($embed_source == 'sway')&&(!url.includes('sway.office')))
            ) {
                url_input.removeClass('cw-embedblock-wrong-plattform');
                this.$('.cw-embedblock-url-info-wrong-plattform').hide();
            }
        } else {
            url_input.removeClass('cw-embedblock-wrong-plattform');
            this.$('.cw-embedblock-url-info-wrong-plattform').hide();
        }
    },

    selectCorrectPlatform() {
        var view = this;
        var url = view.$('.cw-embedblock-url').val();
        var platforms = [
            'vimeo',
            'youtube',
            'giphy',
            'flickr',
            'sway',
            'sway.office',
            'spotify',
            'deviantart',
            'sketchfab',
            'codesandbox',
            'codepen',
            'ethfiddle',
            'slideshare',
            'speakerdeck',
            'audiomack',
            'kidoju',
            'learningapps',
            'soundcloud'
        ];
        $.each(platforms, function(key, platform){
            if(url.includes(platform)) {
                view.$('select.cw-embedblock-source option[value="'+platform+'"]').prop('selected', true);
            }
        });
        if(url.includes('youtu.be')) {
            view.$('select.cw-embedblock-source option[value="youtube"]').prop('selected', true);
        }
        view.selectPlatform();
        view.checkURL();
    },

    toggleTime() {
        var use_start_time = this.$('.cw-embedblock-time-start-check').prop( "checked" );
        if (use_start_time){
            this.$('.cw-embedblock-time-start').prop( "disabled", false );
            this.$('.cw-embedblock-time-end-check').prop( "disabled", false );
            var use_end_time = this.$('.cw-embedblock-time-end-check').prop( "checked" );
            if (use_end_time){
                this.$('.cw-embedblock-time-end').prop( "disabled", false );
                this.timeValidator();
            } else {
                this.$('.cw-embedblock-time-end').prop( "disabled", true );
            }
        } else {
            this.$('.cw-embedblock-time-start').prop( "disabled", true );
            this.$('.cw-embedblock-time-end').prop( "disabled", true );
            this.$('.cw-embedblock-time-end-check').prop( "disabled", true );
        }
    },

    timeValidator() {
        if (this.$('.cw-embedblock-time-end-check').prop( "checked" )) {
            var start = parseInt (60 * this.$('.cw-embedblock-time-start-min').val()) + parseInt (this.$('.cw-embedblock-time-start-sec').val());
            var end = parseInt(60 * this.$('.cw-embedblock-time-end-min').val()) + parseInt(this.$('.cw-embedblock-time-end-sec').val());
            if (end <= start) {
                this.$('.cw-embedblock-time-end').addClass('cw-embedblock-time-warning');
                this.$('.cw-embedblock-time-end-exclaim').show();
            } else {
                this.$('.cw-embedblock-time-end').removeClass('cw-embedblock-time-warning');
                this.$('.cw-embedblock-time-end-exclaim').hide();
            }
        }
    },

    setTime() {
        var time = this.$('.cw-embedblock-time-stored').val();
        if (time != '') {
            time = JSON.parse(time);
            this.$('.cw-embedblock-time-start-min').val(parseInt(time.start/60));
            this.$('.cw-embedblock-time-start-sec').val(parseInt(time.start%60));
            this.$('.cw-embedblock-time-end-min').val(parseInt(time.end/60));
            this.$('.cw-embedblock-time-end-sec').val(parseInt(time.end%60));
            this.$('.cw-embedblock-time-start-check').prop( "checked", true );
            if (time.end) {
                this.$('.cw-embedblock-time-end-check').prop( "checked", true );
            }
            this.toggleTime();
        }
    },

    isImage() {
        var $embed_source = this.$('select.cw-embedblock-source option:selected').val();
        if ($embed_source == 'giphy' || $embed_source == 'deviantart' || $embed_source == 'flickr') {
            return true;
        } else {
            return false;
        }
    }
});
