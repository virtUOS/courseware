import $ from 'jquery'
import StudentView from 'js/student_view'
import helper from 'js/url'

export default StudentView.extend({
    events: {
        'click button[name=play]':   'playVideo',
        'click button[name=stop]':   'stopVideo',
        'click .cw-iav-stop-button-continue': 'continueVideo',
        'click .cw-iav-test-button-continue': 'continueVideo',
        'click button[name=submit-exercise]': 'submitExercise'
    },

    initialize() {},

    render() {
        return this;
    },

    postRender() {
        var $view =  this,
        $player_element = $view.$('.cw-iav-player').get(0);

        if (typeof $player_element !== 'undefined') {
            if ($player_element.readyState >= 2) {      //if firefox
                $view.setupVideo();
            } else {    // else may be chrome
                $player_element.addEventListener('loadedmetadata', function () {
                    $view.setupVideo();
                }, false);
            }
            // TODO this code from vips.js should be called by vips.js
            if (this.$('.cw-iav-test-wrapper').hasClass('vips14')) {
                this.$('.rh_list').sortable({
                    item: '> .rh_item',
                    tolerance: 'pointer',
                    connectWith: '.rh_list',
                    update: function(event, ui) {
                        if (ui.sender) {
                            ui.item.find('input').val(jQuery(this).data('group'));
                        }
                    },
                    over: function(event, ui) {
                        jQuery(this).addClass('hover');
                    },
                    out: function(event, ui) {
                        jQuery(this).removeClass('hover');
                    },
                    receive: function(event, ui) {
                        var sortable = jQuery(this);
                        var container = sortable.closest('tbody').find('.answer_container');
            
                        // default answer container can have more items
                        if (sortable.children().length > 1 && !sortable.is(container)) {
                            sortable.find('.rh_item').each(function(i) {
                                if (!ui.item.is(this)) {
                                    jQuery(this).find('input').val(-1);
                                    jQuery(this).detach().appendTo(container)
                                                .css('opacity', 0).animate({opacity: 1});
                                }
                            });
                        }
                    },
                });
            } else {
                this.$('.rh_list').sortable({
                    axis: 'y',
                    containment: 'parent',
                    item: '> .rh_item',
                    tolerance: 'pointer',
                    update: this.rh_move_choice
                });
            }
        }

    },

    setupVideo() {
        var $view =  this,
        $player = $view.$('.cw-iav-player'),
        $player_element = $player.get(0),
        $duration = parseInt($player_element.duration),
        $playbutton = $view.$('.cw-iav-playbutton'),
        $range = $view.$('.cw-iav-range'),
        $time = $view.$('.cw-iav-time');
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
          if ($source.indexOf('mp4') > -1) {
            $(this).prop('type', 'audio/mp4')
          }
          if ($source.indexOf('ogg') > -1) {
            $(this).prop('type', 'audio/ogg')
          }
          if ($source.indexOf('webm') > -1) {
            $(this).prop('type', 'audio/webm')
          }
        });

        var overlay_json = $view.$('.overlay-json').val();
        if (overlay_json != "") {
            overlay_json = JSON.parse(overlay_json);
            $view.addOverlays(overlay_json);
        }

        var stop_json = $view.$('.stop-json').val();
        if (stop_json != "") {
            stop_json = JSON.parse(stop_json);
            $view.addStops(stop_json);
        }
        var test_json = $view.$('.test-json').val();
        if (test_json != "") {
            test_json = JSON.parse(test_json);
            $view.setupTests(test_json);
        }
        $player_element.addEventListener('timeupdate',function () {
            var $current = parseInt($player.prop('currentTime'));
            try {
                $range.slider( 'value', $current );
            }
            catch(err) {
            }
            $time.html($view.displayTimer($current, $duration));
            $view.interactionEventOverlay($current, overlay_json);
            $view.interactionEventStopAndTest($current);
            if ($current == $duration) {
                $view.onWatched();
            }
        }, false);

        $player_element.addEventListener('ended', function () {
          $playbutton.removeClass('playing');
          $player.prop('currentTime', 0);
        }, false);
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
    },

    interactionEventOverlay($time, overlay_json) {
        if (overlay_json == "") { return; }
        var $view = this;

        for (var i in overlay_json) {
            var $overlay = $view.$('.cw-iav-overlay-content');
            if (($time >= overlay_json[i].start ) && ($time <= overlay_json[i].end)) {
                $overlay.filter('[data-overlayid='+overlay_json[i].id+']').show('fade', 800);
            } else {
                $overlay.filter('[data-overlayid='+overlay_json[i].id+']').hide('fade', 800);
            }
        }
    },

    interactionEventStopAndTest($time) {
        var $view = this;
        var stop_json = $view.$('.stop-json').val();
        if (stop_json != "") {
            stop_json = JSON.parse(stop_json);
        }
        var test_json = $view.$('.test-json').val();
        if (test_json != "") {
            test_json = JSON.parse(test_json);
        }
        if ((stop_json == "") && (test_json == "")){ return; }

        var $range = $view.$('.cw-iav-range'),
            $player = $view.$('.cw-iav-player'),
            $playbutton = $view.$('.cw-iav-playbutton'),
            interactionevents = [];
        for (var i in stop_json) {
            if (stop_json[i].solved == true) {
                continue;
            }
            if ($time >= stop_json[i].moment){
                interactionevents.push({"type": "stop", "id": stop_json[i].id , "moment":  stop_json[i].moment});
            }
        }
        for (var i in test_json) {
            if ((test_json[i].use_test == false)||(test_json[i].solved == true)) {
                continue;
            }
            if ($time >= test_json[i].moment){
                interactionevents.push({"type": "test", "id": test_json[i].test_id , "moment":  test_json[i].moment});
            }
        }
        if (interactionevents.length > 0) {
            interactionevents.sort(function (a, b) {
                return a.moment - b.moment;
            });
            let interaction = false;
            if (interactionevents[0].type == 'stop') {
                let $stop = $view.$('.cw-iav-stop-content[data-stopid='+interactionevents[0].id+']')
                if ($stop.is(':hidden')) {
                    $stop.show('fade', 800);
                    interaction = true;
                }
            }

            if (interactionevents[0].type == 'test') {
                let $test = $view.$('.cw-iav-test-content[data-testid='+interactionevents[0].id+']');
                if ($test.is(':hidden')) {
                    $test.show('fade', 800);
                    $(window).trigger('resize');
                    interaction = true;
                }
            }

            if (interaction) {
                $player.prop('currentTime', interactionevents[0].moment);
                try {$range.slider('disable');}
                catch(err) {}
                $player.trigger('pause');
                $playbutton.removeClass('playing').hide();
                $playbutton.removeClass('playing').hide();
            }
        }
    },

    addOverlays(overlay_json) {
        var $view = this;
        var $overlay_wrapper = $view.$('.cw-iav-overlay-wrapper');
        var html = "";
        for (var i in overlay_json) {
            var $item = $view.$('.cw-iav-overlay-content-default').clone();
            $item.attr('data-overlayid', overlay_json[i].id);
            $item.find('.cw-iav-overlay-content-title').text(overlay_json[i].title);
            $item.find('.cw-iav-overlay-content-text').text(overlay_json[i].content); 
            $item.removeClass('cw-iav-overlay-content-default').addClass('overlay-content-'+overlay_json[i].id).addClass(overlay_json[i].position).addClass(overlay_json[i].type).addClass(overlay_json[i].color);

            $($item).appendTo($overlay_wrapper);
        }
        $view.$('.cw-iav-overlay-content.overlay-content-default').remove();
        $view.$('.cw-iav-overlay-content').hide();
    },

    addStops(stop_json) {
        var $view = this;
        var $stop_wrapper = $view.$('.cw-iav-stop-wrapper');
        var html = "";
        for (var i in stop_json) {
            var $item = $view.$('.cw-iav-stop-content-default').clone();
            $item.attr('data-stopid', stop_json[i].id);
            $item.attr('data-stop-moment', stop_json[i].moment);
            $item.find('.cw-iav-stop-content-title').text(stop_json[i].title);
            $item.find('.cw-iav-stop-content-text').text(stop_json[i].content); 
            $item.find('.cw-iav-stop-button-continue').attr('data-stopid', stop_json[i].id); 
            $item.removeClass('cw-iav-stop-content-default').addClass('stop-content-'+stop_json[i].id).addClass(stop_json[i].type).addClass(stop_json[i].position).addClass(stop_json[i].color);

            $($item).appendTo($stop_wrapper);
        }
        $view.$('.cw-iav-stop-content.cw-iav-stop-content-default').remove();
        $view.$('.cw-iav-stop-content').hide();
    },

    setupTests(test_json) {
        for (var i in test_json) {
            let $test = this.$('.cw-iav-test-content[data-testid="'+test_json[i].test_id+'"]');
            $test.attr('data-test-moment', test_json[i].moment);
        }
    },

    playVideo() {
        var $view = this,
            $player = $view.$('.cw-iav-player'),
            $player_element = $player[0],
            $range = $view.$('.cw-iav-range'),
            $playbutton = $view.$('.cw-iav-playbutton');

        if ($player_element.paused) {
            $player.trigger('play');
            $playbutton.addClass('playing');
        } else {
            $player.trigger('pause');
            $playbutton.removeClass('playing');
        }
    },

    stopVideo() {
        var $view = this,
            $player = $view.$('.cw-iav-player'),
            $playbutton = $view.$('.cw-iav-playbutton'),
            $range = $view.$('.cw-iav-range');

        $player.trigger('pause');
        $range.slider('enable');
        $view.$('.cw-iav-overlay-content').hide();
        $view.$('.cw-iav-stop-content').hide();
        $player.prop('currentTime', 0);
        $playbutton.removeClass('playing').show();
        if ($view.$('.stop-json').val() != "") {
            var stop_json = JSON.parse($view.$('.stop-json').val());
            for (var i = 0; i < stop_json.length; i++) {
                stop_json[i].solved = false;
            }
            $view.$('.stop-json').val(JSON.stringify(stop_json));
        }
    },

    continueVideo(event) {
        var $view = this,
            $player = $view.$('.cw-iav-player'),
            $playbutton = $view.$('.cw-iav-playbutton'),
            $range = $view.$('.cw-iav-range'),
            $continue_button = this.$(event.target),
            item_id = '';

        $player.trigger('play');
        $playbutton.addClass('playing').show();
        $range.slider('enable');

        if ($continue_button.hasClass('cw-iav-stop-button-continue')) {
            item_id = event.currentTarget.attributes.getNamedItem('data-stopid').value;
            if ($view.$('.stop-json').val() != "") {
                var stop_json = JSON.parse($view.$('.stop-json').val());
                stop_json[item_id].solved = true;
                $continue_button.closest('.cw-iav-stop-content').addClass('cw-iav-stop-solved');
                $view.$('.stop-json').val(JSON.stringify(stop_json));
            }
        }
        if ($continue_button.hasClass('cw-iav-test-button-continue')) {
            item_id = event.currentTarget.attributes.getNamedItem('data-testid').value;
            if ($view.$('.test-json').val() != "") {
                var test_json = JSON.parse($view.$('.test-json').val());
                $.each(test_json, function(i, item){
                    if (item.test_id == item_id){
                        item.solved = true;
                        $continue_button.closest('.cw-iav-test-content').addClass('cw-iav-test-solved');
                    }
                });
                $view.$('.test-json').val(JSON.stringify(test_json));
            }
        }
        $view.$('.cw-iav-stop-content').hide('fade', 800);
        $view.$('.cw-iav-test-content').hide('fade', 800);
    },

    submitExercise(event) {
        var $form = this.$(event.target).closest('form'),
        view = this,
        top = document.body.scrollTop,
        $player = view.$('.cw-iav-player'),
        $current = $player.prop('currentTime'),
        $test_json = view.$('.test-json').val(),
        $stop_json = view.$('.stop-json').val(),
        $block = this.$el.parent();

        helper.callHandler(this.model.id, 'exercise_submit', $form.serialize())
        .then(function (resp) {
            return view.renderServerSide();
        }).then(function () {
            view.$('.test-json').val($test_json);
            view.$('.stop-json').val($stop_json);
            $player.prop('currentTime', $current);
            
            document.body.scrollTop = top;
        })
        .catch(function () {
            console.log('failed to store the solution');
        });

        return false;
    },

    onWatched() {
        helper
          .callHandler(this.model.id, 'watched', {})
          .catch(function (error) {
            var errorMessage = 'Could not update the block: ' + $.parseJSON(error.responseText).reason;
            alert(errorMessage);
            console.log(errorMessage, arguments);
        });
    },

    // TODO this code from vips.js should be called by vips.js
    rh_move_choice(event, ui)
    {
        jQuery(this).children().each(function(i) {
            jQuery(this).find('input').val(i);
        });
    }

});

