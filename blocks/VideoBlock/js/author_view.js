import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({
    events: {
        'click button[name=save]': 'saveVideo',
        'click button[name=cancel]': 'switchBack',
        'change select.videotype': 'selection',
        'click button[name=preview]': 'showPreview',
        'click button[name=addmediatype]': 'addmediatype'
    },

    initialize() {
        Backbone.on('beforemodeswitch', this.onModeSwitch, this);
        Backbone.on('beforenavigate', this.onNavigate, this);
    },

    onNavigate(event) {
        if (!$('section .block-content button[name=save]').length) {
          return;
        }
        if (event.isUserInputHandled) {
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

    render() {
        return this;
    },

    postRender() {
        var url = this.$('.cw-videoblock-stored-url').val();
        var aspect = this.$('.cw-videoblock-stored-aspect').val();
        var webvideosrc = this.$('.webvideodata').val();
        var videotype = '';
        if (url != '') {
            videotype = 'url';
        }
        if (webvideosrc != '') {
            videotype = 'webvideo'
        }
        if (videotype == '') {
            return;
        }
        this.$('.videotype option[value=' + videotype + ']').attr('selected', true);
        this.$('.cw-videoblock-aspect option[value=' + aspect + ']').attr('selected', true);

        this.selection();
        this.showPreview();
    },

    saveVideo() {
        var view = this;
        var videotype = this.$('.videotype').val();
        var status = this.$('.status');
        var aspect = this.$('select.cw-videoblock-aspect').val();
        var videoTitle = this.$el.find('.videotitle').val();
        var url, webvideo, webvideosettings;

        if (videotype == 'webvideo') {
            url = '';
            webvideosettings = 'controls ';
            webvideo = new Array();
                this.$('.videosource-webvideo > .webvideo').each(function () {
                var src = $(this).find('.webvideosrc').val();
                var type = $(this).find('.webvideosrc-mediatype').val();
                var query = $(this).find('.webvideosrc-mediaquery').val();
                var media = '', attr = '';
                switch (query) {
                    case 'normal':
                        media = '';
                        break;
                    case 'large':
                        media = 'screen and (min-device-width:801px)';
                        break;
                    case 'small':
                        media = 'screen and (max-device-width:800px)';
                        break;
                }
                if (src != '') {
                    webvideo.push({ src, type, query, media, attr });
                }
            });
            if (view.$el.find('.videoautostart').is(':checked')) {
                webvideosettings += 'autoplay ';
            }
            webvideo = JSON.stringify(webvideo);
        } else {
          url = this.$el.find('.videosrc').val()
          webvideo = '';
          webvideosettings = '';
        }
        status.text('Speichere Änderungen...');

        helper.callHandler(this.model.id, 'save', {
          url, webvideo, webvideosettings, videoTitle, aspect
        }).then(function () {
          status.text('Änderungen wurden gespeichert.');
          view.switchBack();
        }).catch(function (error) {
          status.text('Fehler beim Speichern: ' + $.parseJSON(error.responseText).reason);
        });
    },

    selection() {
        var videotype = this.$('.videotype').val();
        var videourl = this.$('.videourl').val();
        this.$el.find('.videosource').hide();
        switch (videotype) {
            case 'webvideo':
                var webvideodata = this.$('.webvideodata').val();
                this.$el.find('.videosource-webvideo').show();
                this.$el.find('video').show();
                this.$el.find('iframe').hide();
                if (webvideodata != "") {
                    videourl = JSON.parse(webvideodata).src;
                }
                this.setVideoData();
                break;
            case 'url':
                this.$el.find('.videosource-url').show();
                this.$el.find('iframe').show();
                this.$el.find('video').hide();
                break;
        }
    },

    addmediatype(event, src, type, query) {
        var $addmediatype = this.$('.addmediatype');
        var $webvideo = this.$('.videosource-webvideo > .webvideo').first().clone();
        $webvideo.find('.webvideosrc').val(src);
        $webvideo.find('.webvideosrc-mediatype option[value="' + type + '"]').prop('selected', true);
        $webvideo.find('.webvideosrc-mediaquery option[value="' + query + '"]').prop('selected', true);
        $webvideo.insertBefore($addmediatype);
    },

    setVideoData() {
        var view = this;
            var webvideodata = view.$el.find('.webvideodata').val();
            var webvideosettingsdata = view.$el.find('.webvideosettingsdata').val();
            if (webvideodata != '[]') {
                webvideodata = $.parseJSON(webvideodata);
                view.$el.find('video').attr('src', webvideodata[0].src);
                $.each(webvideodata, function (key, value) {
                    view.addmediatype(null, value.src, value.type, value.query);
                });
                view.$('.videosource-webvideo > .webvideo').first().remove();
            }
            if (webvideosettingsdata.indexOf('autoplay') > -1) {
                view.$el.find('.videoautostart').prop('checked', true);
            }
    },

    showPreview() {
        this.$('iframe').attr('src', this.$('.cw-videoblock-stored-url').val());
        this.$('video').attr('src', this.$('.webvideosrc').val());
        this.$('.video-wrapper').removeClass('aspect-43').removeClass('aspect-169').addClass(this.$('.cw-videoblock-aspect').val());
    }
});
