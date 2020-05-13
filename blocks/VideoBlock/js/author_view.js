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
        'click button[name=addmediatype]': 'addmediatype',
        'click button.removemediatype': 'removemediatype',
        'change select.cw-webvideo-source': 'toggleSourceEvent',

        'click .cw-videoblock-recorder-start': 'startRecording',
        'click .cw-videoblock-recorder-stop': 'stopRecording',
        'click .cw-videoblock-recorder-reset': 'resetRecorder'
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
        } else {
            videotype = 'webvideo';
        }
        this.$('.videotype option[value=' + videotype + ']').attr('selected', true);
        this.$('.cw-videoblock-aspect option[value=' + aspect + ']').attr('selected', true);
        this.selection();
        this.showPreview();
        if ($('.removemediatype').length <= 1) {
            $('.removemediatype').hide();
        }

        if(this.$('.webvideosettingsdata').val().indexOf('autoplay') > -1) {
            this.$('.videoautostart').attr('checked', true)
        }
        if(this.$('.webvideosettingsdata').val().indexOf('oncontextmenu') > -1) {
            this.$('.videodisablecontext').attr('checked', true)
        }
    },

    saveVideo() {
        var view = this;
        var videotype = this.$('.videotype').val();
        var status = this.$('.status');
        var aspect = this.$('select.cw-videoblock-aspect').val();
        var videoTitle = this.$('.videotitle').val();
        var url = '';
        var webvideo = '';
        var webvideosettings = '';
        var recording = '';

        switch(videotype) {
            case 'webvideo':
                webvideosettings = 'controls ';
                webvideo = new Array();
                this.$('.videosource-webvideo > .webvideo').each(function () {
                    let source = $(this).find('.cw-webvideo-source').val();
                    let src = '';
                    let file_id = '';
                    let file_name = '';
                    if (source == 'url') {
                        src = $(this).find('.cw-webvideo-source-url').val();
                    } else {
                        src =  $(this).find('.cw-webvideo-source-file option:selected').attr('file_url');
                        file_id =  $(this).find('.cw-webvideo-source-file option:selected').attr('file_id');
                        file_name =  $(this).find('.cw-webvideo-source-file option:selected').attr('file_name');
                    }
                    let type = $(this).find('.webvideosrc-mediatype').val();
                    let query = $(this).find('.webvideosrc-mediaquery').val();
                    let media = '', attr = '';
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
                        webvideo.push({ src, source, type, query, media, attr, file_id, file_name });
                    }
                });
                if (view.$('.videoautostart').is(':checked')) {
                    webvideosettings += 'autoplay ';
                }
                if (view.$('.videodisablecontext').is(':checked')) {
                    webvideosettings += "oncontextmenu='return false;' ";
                }
                webvideo = JSON.stringify(webvideo);
                break;
            case 'url':
                url = this.$('.videosrc').val();
                break;
            case 'recorder':
            if (this.blob == null){
                    view.$('.cw-video-empty-recording').slideDown(250).delay(2500).slideUp(250);
                    return;
                }
                recording = this.blob.base64data;
                break;
        }
        status.text('Speichere Änderungen...');

        helper.callHandler(this.model.id, 'save', {
          url, webvideo, webvideosettings, videoTitle, aspect, recording, videotype
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
        this.$('.videosource').hide();
        this.$('.cw-webvideo-source-file-info').hide();
        this.$('.button[name="preview"]').show();
        this.$('.cw-videoblock-recorder-wrapper').hide();
        this.$('.cw-videoblock-aspect').attr('disabled', false);
        let video = this.$('.video-wrapper video')[0];
        video.srcObject = null;
        video.controls = true;
        video.pause();

        switch (videotype) {
            case 'webvideo':
                var webvideodata = this.$('.webvideodata').val();
                this.$('.videosource-webvideo').show();
                this.$('video').show();
                this.$('iframe').hide();
                if ((webvideodata == '') || (webvideodata == 'null')|| (webvideodata == null)){
                    break;
                }
                this.setVideoData();
                break;
            case 'url':
                this.$('.videosource-url').show();
                this.$('iframe').show();
                this.$('video').hide();
                break;
            case 'recorder':
                var $view = this;
                $view.resetRecorder();
                $view.$('.button[name="preview"]').hide();
                $view.$('.cw-videoblock-aspect option[value="aspect-169"]').attr('selected', true);
                $view.$('.cw-videoblock-aspect').attr('disabled', true);
                $view.$('.cw-videoblock-recorder-wrapper').show();
                $view.$('.cw-videoblock-recording-info').hide();
                $view.$('.cw-videoblock-recorder-browser-info').hide();
                $view.$('.cw-videoblock-recorder-start').hide();
                $view.$('.cw-videoblock-recorder-stop').hide();
                $view.$('.cw-videoblock-recorder-enable-info').show();
                $view.$('.cw-videoblock-recorder-device-info').hide();
                if (!window.MediaRecorder) {
                    $view.$('.cw-videoblock-recorder-enable-info').hide();
                    $view.$('.cw-videoblock-recorder-browser-info').show();
                    break;
                }


                navigator.mediaDevices.enumerateDevices()
                    .then(function(deviceInfos){
                        let videoInput = false;
                        let audioInput = false;
                        $.each(deviceInfos, function(){
                            if (this.kind == 'videoinput') {
                                videoInput = true;
                            }
                            if (this.kind == 'audioinput') {
                                audioInput = true;
                            }
                        });
                        if (!(videoInput && audioInput)) {
                            $view.$('.cw-videoblock-recorder-enable-info').hide();
                            $view.$('.cw-videoblock-recorder-device-info').show();
                        } else {
                            navigator.mediaDevices.getUserMedia({
                                audio: true, 
                                video: {
                                    width: { min: 1024, ideal: 1280, max: 1920 },
                                    height: { min: 576, ideal: 720, max: 1080 }
                                }
                            }).then(_stream => {
                                $view.stream = _stream;
            
                                $view.$('.cw-videoblock-recorder-start').show();
                                $view.$('.cw-videoblock-recorder-enable-info').hide();
                                let options = {
                                    audioBitsPerSecond : 128000,
                                    videoBitsPerSecond : 1400000,
                                    mimeType : 'video/webm'
                                }
                                $view.recorder = new MediaRecorder($view.stream, options);
                                let video = $view.$('.video-wrapper video')[0];
                                video.srcObject = $view.stream;
                                video.onloadedmetadata = function(e) {
                                    if(video.srcObject != null) {
                                        video.play();
                                    }
                                };
            
                                $view.recorder.ondataavailable = e => {
                                    $view.chunks.push(e.data);
                                    if($view.recorder.state == 'inactive')  {
                                        video.pause();
                                        video.srcObject = null;
                                        $view.makeBlob();
                                    }
                                };
                            });
                        }
                    });

                break;
        }
    },

    addmediatype(event, src, type, query, source) {
        var $addmediatype = this.$('.addmediatype');
        var $webvideo = this.$('.videosource-webvideo > .webvideo').first().clone();
        if (source == 'url') {
            $webvideo.find('.cw-webvideo-source option[value="url"]').prop('selected', true);
            $webvideo.find('.cw-webvideo-source-url').val(src);
        } else {
            $webvideo.find('.cw-webvideo-source option[value="file"]').prop('selected', true);
            $webvideo.find('.cw-webvideo-source-file option[file_url="'+src+'"]').prop('selected', true);
        }
        $webvideo.find('.webvideosrc-mediatype option[value="' + type + '"]').prop('selected', true);
        $webvideo.find('.webvideosrc-mediaquery option[value="' + query + '"]').prop('selected', true);
        $webvideo.insertBefore($addmediatype);
        if (source == 'url') {
            $webvideo.find('.cw-webvideo-source-url').show();
            $webvideo.find('.cw-webvideo-source-file').hide();
            $webvideo.find('.cw-webvideo-source-url-info').show();
            $webvideo.find('.cw-webvideo-source-file-info').hide();
        } else {
            $webvideo.find('.cw-webvideo-source-file').show();
            $webvideo.find('.cw-webvideo-source-url').hide();
            $webvideo.find('.cw-webvideo-source-url-info').hide();
            $webvideo.find('.cw-webvideo-source-file-info').show();
        }
        $('.removemediatype').show();
    },

    removemediatype(event) {
        var $webvideo = $(event.currentTarget).parent('.webvideo');

        if ($webvideo.siblings('.webvideo').length != 0) {
            $webvideo.remove();
        }
        if ($('.removemediatype').length == 1) {
            $('.removemediatype').hide();
        }
    },

    setVideoData() {
        var view = this;
            var webvideodata = view.$('.webvideodata').val();
            var webvideosettingsdata = view.$('.webvideosettingsdata').val();
            if (webvideodata != '[]') {
                webvideodata = $.parseJSON(webvideodata);
                view.$('video').attr('src', webvideodata[0].src);
                $.each(webvideodata, function (key, value) {
                    view.addmediatype(null, value.src, value.type, value.query, value.source);
                });
                view.$('.videosource-webvideo > .webvideo').first().remove();
            }
            if (webvideosettingsdata.indexOf('autoplay') > -1) {
                view.$('.videoautostart').prop('checked', true);
            }
    },

    showPreview() {
        var videotype = this.$('.videotype').val();
        if (videotype == 'webvideo') {
            let webvideo = this.$('.webvideo').first();
            let video_url = '';
            if (webvideo.find('.cw-webvideo-source').val() == 'url') {
                video_url = webvideo.find('.cw-webvideo-source-url').val();
            } else {
                video_url = webvideo.find('.cw-webvideo-source-file option:selected').attr('file_url');
            }
            this.$('video').attr('src', video_url);
        }
        this.$('iframe').attr('src', this.$('.cw-videoblock-stored-url').val());
        this.$('.video-wrapper').removeClass('aspect-43').removeClass('aspect-169').addClass(this.$('.cw-videoblock-aspect').val());
    },

    toggleSourceEvent(event) {
        var source = $(event.currentTarget);
        this.toggleSource(source);
    },

    toggleSource(source) {
        var webvideo = source.parents('.webvideo');
        this.$('.cw-webvideo-source-url-info').hide();
        this.$('.cw-webvideo-source-file-info').hide();
        var file = webvideo.find('.cw-webvideo-source-file'),
            url = webvideo.find('.cw-webvideo-source-url');
        if (source.val() == 'url') {
            file.hide();
            url.show();
            this.$('.cw-webvideo-source-url-info').show();
            this.$('.cw-webvideo-source-file-info').hide();

        } else {
            file.show();
            url.hide();
            this.$('.cw-webvideo-source-url-info').hide();
            this.$('.cw-webvideo-source-file-info').show();
        }
    },


    startRecording() {
        this.chunks = [];
        this.recorder.start();
        this.$('.cw-videoblock-recording-info').show();
        this.setTimer(0);
  
        this.$('.cw-videoblock-recorder-start').hide();
        this.$('.cw-videoblock-recorder-stop').show();
    },
  
    stopRecording() {
        this.recorder.stop();
        this.$('.cw-videoblock-recording-info').hide();
        this.$('.cw-videoblock-recorder-stop').hide();
    },
  
    resetRecorder() {
        let video = this.$('.video-wrapper video')[0];
        video.controls = false;
        video.src = '';
        this.blob = null;
        this.chunks = [];
        video.srcObject = this.stream;
        this.$('.cw-videoblock-recorder-start').show();
        this.$('.cw-videoblock-recorder-stop').hide();
        this.$('.cw-videoblock-recorder-reset').hide();
    },
  
    makeBlob(){
        var $view = this;
        let video = $view.$('.video-wrapper video')[0];
        this.blob = new Blob($view.chunks, {type: 'video/webm' })
        let url = URL.createObjectURL(this.blob);
        video.controls = true;
        video.src = url;
        $view.$('.cw-videoblock-recorder-reset').show();
  
        var reader = new FileReader();
        reader.readAsDataURL($view.blob);
        reader.onloadend = function() {
           $view.blob.base64data = reader.result.toString();                
       }
    }, 
  
    setTimer(i) {
        var $view = this;
        if (this.recorder.state == 'recording') {
            this.$('.cw-videoblock-recording-timer').text(this.seconds2time(i));
            i++;
            setTimeout(function(){ $view.setTimer(i); }, 1000);
        }
     },
  
    seconds2time(seconds) {
      var hours   = Math.floor(seconds / 3600),
          minutes = Math.floor((seconds - (hours * 3600)) / 60),
          time = '';
  
      seconds = seconds - (hours * 3600) - (minutes * 60);
  
      if (hours != 0) {
        time = hours + ':';
      }
      if (minutes != 0 || time !== '') {
        minutes = (minutes < 10 && time !== '') ? '0' + minutes : String(minutes);
        time += minutes + ':';
      }
      if (time === '') {
        time = (seconds < 10) ? '0:0' + seconds : '0:' + seconds;
      }
      else {
        time += (seconds < 10) ? '0' + seconds : String(seconds);
      }
      return time;
    }
});
