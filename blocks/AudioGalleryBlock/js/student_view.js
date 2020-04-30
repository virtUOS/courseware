import jQuery from 'jquery'
import StudentView from 'js/student_view'
import slick from 'slick-carousel'
import helper from 'js/url'


export default StudentView.extend({
  events: {
        'click .cw-audio-gallery-record-button-enable': 'enableRecorder',
        'click .cw-audio-gallery-record-button-start' : 'startRecording',
        'click .cw-audio-gallery-record-button-restart' : 'restartRecording',
        'click .cw-audio-gallery-record-button-stop' : 'stopRecording', 
        'click .cw-audio-gallery-record-button-store' : 'storeRecording',
        'click .cw-audio-gallery-record-button-delete' : 'deleteRecord'
      },

  initialize() {
  },

  render() {
    return this;
  },

  postRender() {
      var $view = this;
      this.$('.cw-audio-gallery-carousel-for').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        arrows: false,
        fade: true,
        asNavFor: $view.$('.cw-audio-gallery-carousel-nav')
      });

      this.$('.cw-audio-gallery-carousel-nav').slick({
        centerMode: true,
        focusOnSelect: true,
        centerPadding: '42px',
        slidesToShow: 3,
        slidesToScroll: 1,
        asNavFor: $view.$('.cw-audio-gallery-carousel-for')
      });

      // fix slick-carousel element width from 100% to width - margin
      var width = $('.cw-audio-gallery-carousel-nav .slick-slide')[0].offsetWidth - 20;
      this.$('.slick-slide').find('.cw-audio-gallery-nav-slide').css('width', width+'px');

      if (!window.MediaRecorder) {
        $view.$('.cw-audio-gallery-record-browser-info').show();
        $view.$('.cw-audio-gallery-record-button-enable').hide();
        return;
      }



      if (this.$('.cw-audio-gallery-record-button-delete.user-record-delete').siblings('.cw-audio-gallery-player').length > 0) {
          this.$('.cw-audio-gallery-record-button-delete.user-record-delete').show();
      }
  },

  enableRecorder() {

    let $view = this;

    this.$('.cw-audio-gallery-record-button-enable').hide();
    this.$('.cw-audio-gallery-record-usermedia-info').show();

    navigator.mediaDevices.getUserMedia({audio: true}).then(_stream => {
      let stream = _stream;
      $view.recorder = new MediaRecorder(stream);

      if($view.$('.cw-audio-gallery-userhasrecord').val() == 'true') {
        $view.$('.cw-audio-gallery-record-button-restart').show();
      }
      if($view.$('.cw-audio-gallery-userhasrecord').val() == 'false') {
        $view.$('.cw-audio-gallery-record-button-start').show();
      }

      $view.$('.cw-audio-gallery-record-usermedia-info').hide();

      $view.recorder.ondataavailable = e => {
        $view.chunks.push(e.data);
        if($view.recorder.state == 'inactive')  $view.makeBlob();
      };
    });

  },

  startRecording() {
      if (!window.MediaRecorder) {
        return;
      }
      this.chunks = [];
      this.recorder.start();
      this.$('.cw-audio-gallery-record-button-delete').hide();
      this.$('.cw-audio-gallery-record-button-start').hide();
      this.$('.cw-audio-gallery-record-button-stop').show();
      this.$('.cw-audio-gallery-recording-info').show();
      this.$('.user-record .cw-audio-gallery-player').remove();
      this.timer = 0;
      this.setTimer();
  },

  restartRecording() {
    if (!window.MediaRecorder) {
      return;
    }
    this.$('.cw-audio-gallery-record-button-restart').hide();
    this.$('.cw-audio-gallery-record-button-store').hide();
    this.resetRecorder();
    this.startRecording();
  },

  stopRecording() {
      this.$('.cw-audio-gallery-recording-info').hide();
      this.$('.cw-audio-gallery-record-button-restart').show();
      this.$('.cw-audio-gallery-record-button-store').show();
      this.$('.cw-audio-gallery-record-button-stop').hide();
      this.recorder.stop();
  },

  resetRecorder() {
      var $view = this;
      $view.blob = null;
      $view.chunks = [];
  },

  makeBlob(){
      var $view = this;
      var control = $view.$('.cw-audio-gallery-content-slide-control')[0];
      this.blob = new Blob($view.chunks, {type: 'audio/ogg' })
      let url = URL.createObjectURL(this.blob),
           audio = document.createElement('audio');
      audio.controls = true;
      audio.src = url;
      audio.classList.add('cw-audio-gallery-player');

      $(audio).insertBefore(this.$('.cw-audio-gallery-record-button-delete.user-record-delete'));

      var reader = new FileReader();
      reader.readAsDataURL($view.blob);
      reader.onloadend = function() {
         $view.blob.base64data = reader.result.toString();
     }

  }, 

  setTimer() {
      var $view = this;
      if (this.recorder.state == 'recording') {
          this.$('.cw-audio-gallery-recording-timer').text(this.seconds2time(this.timer));
          this.timer++;
          setTimeout(function(){ $view.setTimer(); }, 1000);
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
  },

  storeRecording() {
    var $view = this;
    this.$('.cw-audio-gallery-record-button-store').hide();
    this.$('.cw-audio-gallery-record-button-delete').show();

    helper
      .callHandler(this.model.id, 'store_recording', {
        audio_file: $view.blob.base64data,
        audio_length : $view.seconds2time($view.timer)
      })
      .then(
        // success
        function () {
        },

        // error
        function (error) {
          var errorMessage = 'Could not update the block: '+$.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
        });
  },

  deleteRecord(event) {
    var $view = this;
    var $del_button = $(event.currentTarget);
    var $uid = $del_button.data('uid');
    helper
      .callHandler(this.model.id, 'delete_record', {
        uid: $uid
      })
      .then(
        // success
        function () {
            $del_button.siblings('.cw-audio-gallery-player').remove();
            $del_button.hide();
            $view.$('.cw-audio-gallery-record-button-restart').hide();
            if($view.recorder) {
                $view.$('.cw-audio-gallery-record-button-start').show();
            }
        },
        // error
        function (error) {
          var errorMessage = 'Error: '+$.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(errorMessage, arguments);
        });
  }

});
