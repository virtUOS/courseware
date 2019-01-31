import jQuery from 'jquery'
import StudentView from 'js/student_view'
import slick from 'slick-carousel'
import helper from 'js/url'


export default StudentView.extend({
  events: {
        'click .cw-audio-gallery-record-button-start' : 'startRecording',
        'click .cw-audio-gallery-record-button-stop' :   'stopRecording'
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

      // hide all buttons 
      this.$('.cw-audio-gallery-recording-info').hide();
      this.$('.cw-audio-gallery-record-button-stop').hide();
      this.$('.cw-audio-gallery-record-button-start').hide();

      navigator.mediaDevices.getUserMedia({audio: true}).then(_stream => {
        let stream = _stream;
        $view.recorder = new MediaRecorder(stream);
        $view.$('.cw-audio-gallery-record-button-start').show();
        $view.recorder.ondataavailable = e => {
          $view.chunks.push(e.data);
          if($view.recorder.state == 'inactive')  $view.makeBlob();
        };
      });
  },

  startRecording() {
      var $view = this;
      if (!window.MediaRecorder) {
        return;
      }
      this.chunks = [];
      this.recorder.start();
      this.$('.cw-audio-gallery-record-button-start').hide();
      this.$('.cw-audio-gallery-record-button-stop').show();
      this.$('.cw-audio-gallery-recording-info').show();
      this.$('.user-record .cw-audio-gallery-player').remove();
      this.timer = 0;
      this.setTimer();

  },

  stopRecording() {
      this.$('.cw-audio-gallery-recording-info').hide();
      this.$('.cw-audio-gallery-record-button-start').show();
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
      
      $(audio).insertBefore(this.$('.cw-audio-gallery-record-button-start'));

      var reader = new FileReader();
      reader.readAsDataURL($view.blob);
      reader.onloadend = function() {
         $view.blob.base64data = reader.result.toString();
         $view.storeRecording();
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
});
