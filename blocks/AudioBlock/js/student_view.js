import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
  events: {
    'click button[name=play]':   'playAudioFile',
    'click button[name=stop]':   'stopAudioFile'
  },

  initialize() { },

  render() {
    return this;
  },

  postRender() {
    if (this.$('.cw-audio-controls').length == 0) {
      return;
    }

    var $view =  this,
        $player = $view.$('.cw-audio-player'),
        $duration = parseInt($player.prop('duration')),
        $playbutton = $view.$('.cw-audio-playbutton'),
        $range = $view.$('.cw-audio-range'),
        $time = $view.$('.cw-audio-time'),
        $music = $player[0];

    if (isNaN($duration)) {
      $duration = 0;
    }

    $time.html($view.displayTimer(0, $duration));

    $range.slider({
      range: 'max',
      min: 0,
      max: $duration,
      value: 0,
      slide( event, ui ) {
        $player.prop('currentTime',ui.value);
        $time.html($view.displayTimer(ui.value, $duration));
      }
    });

    $player.find('source').each(function () {
      var $source = $(this).prop('src');
      if ($source.indexOf('ogg') > -1) {
        $(this).prop('type', 'audio/ogg')
      }
      if ($source.indexOf('wav') > -1) {
        $(this).prop('type', 'audio/wav')
      }
      // default: type='audio/mpeg'
    });

    $music.addEventListener('timeupdate',function () {
      var $current = parseInt($player.prop('currentTime'));
      $range.slider( 'option', 'value', $current );
      $time.html($view.displayTimer($current, $duration));
    }, false);

    $music.addEventListener('ended', function () {
      $playbutton.removeClass('cw-audio-playbutton-playing');
      $player.prop('currentTime', 0);
    }, false);
  },

  playAudioFile() {
    var $view =  this,
        $player = $view.$('.cw-audio-player'),
        $range = $view.$('.cw-audio-range'),
        $playbutton = $view.$('.cw-audio-playbutton');

    if (isNaN(parseInt($player.prop('duration')))) {
      return;
    }

    if ($range.slider('option', 'max') == 0) {
      console.log('max is null');
      this.postRender();
    }

    if (!$playbutton.hasClass('cw-audio-playbutton-playing')) {
      $playbutton.addClass('cw-audio-playbutton-playing');
      $player.trigger('play');
    } else {
      $playbutton.removeClass('cw-audio-playbutton-playing');
      $player.trigger('pause');
    }
    if ($playbutton.attr('played') != '1') {
      helper
        .callHandler(this.model.id, 'play', {})
        .then(function () {
          $playbutton.attr('played', '1');
        }).catch(function (error) {
          var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
          alert(errorMessage);
          console.log(error, errorMessage, arguments);
        });
    }
  },

  stopAudioFile() {
    var $view =  this,
        $playbutton = $view.$('.cw-audio-playbutton'),
        $player = $view.$('.cw-audio-player');

    $playbutton.removeClass('cw-audio-playbutton-playing');
    $player.trigger('pause');
    $player.prop('currentTime', 0);
  },

  displayTimer($current, $duration) {
    if (isNaN($duration) || ($duration == 0)) {
      return '';
    } else {
      return this.seconds2time($current) + '/' + this.seconds2time($duration);
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
