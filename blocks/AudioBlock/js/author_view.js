import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({

  events: {
    'click button[name=save]':   'onSave',
    'click button[name=cancel]': 'switchBack',
    'change select.cw-audioblock-source': 'selectSource',
    'click .cw-audioblock-recorder-start': 'startRecording',
    'click .cw-audioblock-recorder-stop': 'stopRecording',
    'click .cw-audioblock-recorder-reset': 'resetRecorder'
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
    $view.$('.cw-audioblock-description').val($view.$('.cw-audioblock-description-stored').val());
    $view.$('select.cw-audioblock-source option[value="'+$view.$('.cw-audioblock-source-stored').val()+'"]').prop('selected', true);
    $view.$('.cw-audioblock-recorder-wrapper').hide();
    $view.$('.cw-audioblock-recorder-warning').hide();
    $view.$('.cw-audioblock-blob-warning').hide();
    $view.recorder = null;
    var $source = $view.$('.cw-audioblock-source-stored').val();

    switch ($source) {
        case 'cw':
            $view.$('input.cw-audioblock-file').hide();
            $view.$('.cw-audioblock-file-input-info').hide();
            $view.$('.cw-audioblock-source option[value="cw"]').prop('selected', true);
            $view.$('select.cw-audioblock-file').select2({
              templateResult: state => {
                if (!state.id) { return state.text; }
                var $state = $(
                  '<span data-audiofile="' + state.element.dataset.audiofile +'">' + state.text + '</span>'
                );
                return $state;
              }
            }
            ).show();
            this.$('select.cw-audioblock-file').val($view.$('.cw-audioblock-id-stored').val()).trigger('change');
            $view.$('.cw-audioblock-file-select-info').show();
            break;
        case 'webaudio':
            $view.$('select.cw-audioblock-file').hide();
            $view.$('.cw-audioblock-file-select-info').hide();
            $view.$('input.cw-audioblock-file').val($view.$('.cw-audioblock-file-stored').val());
            $view.$('.cw-audioblock-source option[value="url"]').prop('selected', true);
            $view.$('.cw-audioblock-file-input-info').show();
            break;
        default:
            $view.$('input.cw-audioblock-file').hide();
            $view.$('.cw-audioblock-file-input-info').hide();
            $view.$('select.cw-audioblock-file').show();
            $view.$('.cw-audioblock-file-select-info').show();
            $view.$('.cw-audioblock-source option[value="cw"]').prop('selected', true);
    }

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
    var $audiodescription = $view.$('.cw-audioblock-description').val();
    var $audiosource = $view.$('.cw-audioblock-source').val();
    var $audiofile, $audioid;

    switch ($audiosource) {
        case 'cw':
            $audiofile = $view.$('select.cw-audioblock-file').find(':selected').data('audiofile');
            $audioid = $view.$('select.cw-audioblock-file').val();
            break;
        case 'webaudio':
            $audiofile = $view.$('input.cw-audioblock-file').val();
            $audioid = '';
            break;
        case 'recorder':
            if($view.recorder == null){
                $view.$('.cw-audioblock-blob-warning').slideDown(250).delay(3500).slideUp(250);
                return;
            }

            if($view.recorder.state == 'recording') {
                $view.$('.cw-audioblock-recorder-warning').slideDown(250).delay(3500).slideUp(250);
                return;
            }

            if($view.blob == null){
                $view.$('.cw-audioblock-blob-warning').slideDown(250).delay(3500).slideUp(250);
                return;
            }

            $audioid = '';
            $audiofile = $view.blob.base64data;
            break;
    }

    helper
      .callHandler(this.model.id, 'save', {
        audio_file: $audiofile,
        audio_file_name: $audiofile,
        audio_id: $audioid,
        audio_description: $audiodescription,
        audio_source: $audiosource
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

  selectSource() {
    var $view = this;
    var $selection = $view.$('.cw-audioblock-source').val();
    $view.$('input.cw-audioblock-file').hide();
    $view.$('.cw-audioblock-file-input-info').hide();
    $view.$('select.cw-audioblock-file').hide();
    $view.$('.cw-audioblock-file-select-info').hide();
    $view.$('.cw-audioblock-recorder-wrapper').hide();
    $view.resetRecorder();

    switch ($selection) {
        case 'cw':
            $view.$('select.cw-audioblock-file').show();
            $view.$('.cw-audioblock-file-select-info').show();
            break;
        case 'webaudio':
            $view.$('input.cw-audioblock-file').show();
            $view.$('.cw-audioblock-file-input-info').show();
            break;
        case 'recorder':
            $view.$('.cw-audioblock-recorder-wrapper').show();
            $view.$('.cw-audioblock-recording-info').hide();
            $view.$('.cw-audioblock-recorder-browser-info').hide();
            $view.$('.cw-audioblock-recorder-start').hide();
            $view.$('.cw-audioblock-recorder-stop').hide();
            $view.$('.cw-audioblock-recorder-device-info').hide();
            if (!window.MediaRecorder) {
                $view.$('.cw-audioblock-recorder-enable-info').hide();
                $view.$('.cw-audioblock-recorder-browser-info').show();
                break;
            }
            navigator.mediaDevices.enumerateDevices()
            .then(function(deviceInfos){
                let audioInput = false;
                $.each(deviceInfos, function(){
                     if (this.kind == 'audioinput') {
                        audioInput = true;
                    }
                });
                if (!( audioInput)) {
                    $view.$('.cw-audioblock-recorder-enable-info').hide();
                    $view.$('.cw-audioblock-recorder-device-info').show();
                } else {
                  navigator.mediaDevices.getUserMedia({audio: true}).then(_stream => {
                      let stream = _stream;

                      $view.$('.cw-audioblock-recorder-start').show();
                      $view.$('.cw-audioblock-recorder-enable-info').hide();
                      $view.recorder = new MediaRecorder(stream);

                      $view.recorder.ondataavailable = e => {
                        $view.chunks.push(e.data);
                        if($view.recorder.state == 'inactive')  $view.makeBlob();
                      };
                    });
                }
            });
            break;
    }

    return;
  },

  startRecording() {
      var $view = this;
      this.chunks = [];
      this.recorder.start();
      this.$('.cw-audioblock-recording-info').show();
      this.setTimer(0);

      this.$('.cw-audioblock-recorder-start').hide();
      this.$('.cw-audioblock-recorder-stop').show();
  },

  stopRecording() {
      this.recorder.stop();
      this.$('.cw-audioblock-recording-info').hide();
      this.$('.cw-audioblock-recorder-stop').hide();
  },

  resetRecorder() {
      var $view = this;
      $view.$('.cw-audioblock-recorder-player audio').remove();
      $view.blob = null;
      $view.chunks = [];
      $view.$('.cw-audioblock-recorder-start').show();
      $view.$('.cw-audioblock-recorder-stop').hide();
      $view.$('.cw-audioblock-recorder-reset').hide();
  },

  makeBlob(){
      var $view = this;
      var player = $view.$('.cw-audioblock-recorder-player')[0];
      this.blob = new Blob($view.chunks, {type: 'audio/ogg' })
      let url = URL.createObjectURL(this.blob),
           audio = document.createElement('audio');
      audio.controls = true;
      audio.src = url;
      player.appendChild(audio);
      $view.$('.cw-audioblock-recorder-reset').show();

      var reader = new FileReader();
      reader.readAsDataURL($view.blob);
      reader.onloadend = function() {
         $view.blob.base64data = reader.result.toString();                
     }
  }, 

  setTimer(i) {
      var $view = this;
      if (this.recorder.state == 'recording') {
          this.$('.cw-audioblock-recording-timer').text(this.seconds2time(i));
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
