import $ from 'jquery'
import Backbone from 'backbone'
import AuthorView from 'js/author_view'
import helper from 'js/url'

export default AuthorView.extend({
    events: {
        'click button[name=save]':              'onSave',
        'click button[name=cancel]':            'switchBack',
        'click button[name=play]':              'playVideo',
        'click .cw-iav-stop-button':            'playVideo',
        'click button[name=stop]':              'stopVideo',
        'click button.overlay-adder':           'addItem',
        'click button.stop-adder':              'addItem',
        'click button.overlay-remover':         'removeItem',
        'click button.stop-remover':            'removeItem',
        'click button[name=overlay-preview]':   'overlayPreview',
        'click button[name=stop-preview]':      'stopPreview',
        'click button[name=video-preview]':     'videoPreview',
        'click button.overlay-list-item':       'editOverlayItem',
        'click button.stop-list-item':          'editStopItem',
        'click button.test-list-item':          'editTestItem',
        'change input.cw-iav-timeinput':        'updateTimeInput',
        'change input.cw-iav-overlay-title':    'updateOverlayTitle',
        'keyup input.cw-iav-overlay-title':     'updateOverlayTitle',
        'change input.cw-iav-stop-title':       'updateStopTitle',
        'keyup input.cw-iav-stop-title':        'updateStopTitle',
        'click .cw-iav-tabs-stops':             'disableItems',
        'click .cw-iav-tabs-overlays':          'disableItems',
        'change select[name=assignment_id]':    'getVipsTests',
        'change select.cw-iav-source':          'toggleSource'
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
        var $player_element = $view.$('.cw-iav-player').get(0);
        var $overlay_adder = $view.$('.overlay-adder').parents('li');
        var $stop_adder = $view.$('.stop-adder').parents('li');
        var $overlay_edit = $view.$('.cw-iav-overlay-edit-wrapper');
        var $stop_edit = $view.$('.cw-iav-stop-edit-wrapper');
        var overlay_data =  $view.$('.overlay-json').val();
        var stop_data =  $view.$('.stop-json').val();
        var source_data =  $view.$('.cw-iav-source-stored').val();
        var html = "";

        $view.$('.cw-iav-author-tabs').tabs();
        if ($view.$(".cw-iav-url-stored").val() == '') {
            $view.$('.cw-iav-wrapper').hide();
            $view.$('.cw-iav-controls').hide();
            return ;
        }
        //$view.$(".cw-iav-url").val($view.$(".cw-iav-url-stored").val());
        
        if (source_data != '') {
            var source_data = JSON.parse(source_data);
            if (source_data.external) {
                this.$('.cw-iav-url').val(source_data.url);
            } else {
                this.$('.cw-iav-video-file option[file_id="'+source_data.file_id+'"]').prop('selected', true);
            }
        }

        if (overlay_data != '') {
            var overlay_json = JSON.parse(overlay_data);
            for (var i in overlay_json) {
                html = '<li><button class="button overlay-list-item" data-overlayid="'+ overlay_json[i].id +'">' + overlay_json[i].title + '</button></li>'
                $(html).insertBefore($overlay_adder);
                var $item = $view.$('.cw-iav-overlay-edit-item.item-default').clone();
                $item.attr('data-overlayid', overlay_json[i].id);
                $item.find('input[name="cw-iav-overlay-title"]').val(overlay_json[i].title);
                $item.find('textarea[name="cw-iav-content"]').val(overlay_json[i].content);
                $item.find('input[name="cw-iav-start"]').val(overlay_json[i].start);
                $item.find('input[name="cw-iav-end"]').val(overlay_json[i].end);
                $item.find('select[name="cw-iav-position"] option[value="'+overlay_json[i].position+'"]').prop('selected', true);
                $item.find('select[name="cw-iav-type"] option[value="'+overlay_json[i].type+'"]').prop('selected', true);
                $item.find('select[name="cw-iav-color"] option[value="'+overlay_json[i].color+'"]').prop('selected', true);
                $item.find('button.overlay-remover').attr('data-overlayid', overlay_json[i].id);
                $item.removeClass('item-default').addClass('item-'+overlay_json[i].id);
                $($item).appendTo($overlay_edit);
            }
        }

        if (stop_data != '') {
            var stop_json = JSON.parse(stop_data);
            for (var i in stop_json) {
                html = '<li><button class="button stop-list-item" data-stopid="'+ stop_json[i].id +'">' + stop_json[i].title + '</button></li>'
                $(html).insertBefore($stop_adder);
                var $item = $view.$('.cw-iav-stop-edit-item.item-default').clone();
                $item.attr('data-stopid', stop_json[i].id);
                $item.find('input[name="cw-iav-stop-title"]').val(stop_json[i].title);
                $item.find('textarea[name="cw-iav-content"]').val(stop_json[i].content);
                $item.find('input[name="cw-iav-moment"]').val(stop_json[i].moment);
                $item.find('select[name="cw-iav-position"] option[value="'+stop_json[i].position+'"]').prop('selected', true);
                $item.find('select[name="cw-iav-type"] option[value="'+stop_json[i].type+'"]').prop('selected', true);
                $item.find('select[name="cw-iav-color"] option[value="'+stop_json[i].color+'"]').prop('selected', true);
                $item.find('button.stop-remover').attr('data-stopid', stop_json[i].id);
                $item.removeClass('item-default').addClass('item-'+stop_json[i].id);
                $($item).appendTo($stop_edit);
            }
        }

        $view.$('select[name="assignment_id"]').trigger('change');
        $view.$('.cw-iav-test-content').hide();
        $player_element.addEventListener('loadeddata', function () {
            $view.setupVideo();
        }, false);
        $view.$('.cw-iav-overlay-edit-item').hide();
        $view.$('.cw-iav-stop-edit-item').hide();
        this.toggleSource();

        return this;
    },

    onSave(event) {
        var $view = this;

        var $overlay_items = $view.$('.cw-iav-overlay-edit-item');
        var overlay_json = [];
        $overlay_items.each(function(index){
            if($(this).hasClass('item-default')) {return true;}
            var json = {
                "id" : $(this).data('overlayid'),
                "start" : $(this).find('input[name="cw-iav-start"]').val(),
                "end" : $(this).find('input[name="cw-iav-end"]').val(),
                "type" : $(this).find('select[name="cw-iav-type"]').val(),
                "position" : $(this).find('select[name="cw-iav-position"]').val(),
                "color" : $(this).find('select[name="cw-iav-color"]').val(),
                "title" : $(this).find('input[name="cw-iav-overlay-title"]').val(),
                "content" : $(this).find('textarea[name="cw-iav-content"]').val()
            };
            overlay_json.push(json);
        });
        overlay_json = JSON.stringify(overlay_json);
        var $stop_items = $view.$('.cw-iav-stop-edit-item');
        var stop_json = [];
        $stop_items.each(function(index){
            if($(this).hasClass('item-default')) {return true;}
            var json = {
                "id" : $(this).data('stopid'),
                "moment" : $(this).find('input[name="cw-iav-moment"]').val(),
                "type" : $(this).find('select[name="cw-iav-type"]').val(),
                "position" : $(this).find('select[name="cw-iav-position"]').val(),
                "color" : $(this).find('select[name="cw-iav-color"]').val(),
                "title" : $(this).find('input[name="cw-iav-stop-title"]').val(),
                "content" : $(this).find('textarea[name="cw-iav-content"]').val()
            };
            stop_json.push(json);
        });
        stop_json = JSON.stringify(stop_json);

        var $assignment_id = $view.$('select[name="assignment_id"]').val();
        var $test_items = $view.$('.cw-iav-test-edit-item');
        var tests_json = [];
        $test_items.each(function(index){
            if($(this).hasClass('cw-iav-test-edit-item-default')) {return true;}
            var json = {
                "test_id" : $(this).data('testid'),
                "moment" : $(this).find('input[name="cw-iav-moment"]').val(),
                "use_test" : $(this).find('.cw-iav-test-use').is(':checked'),
                "title": $(this).find('input.cw-iav-test-title').val()
            };
            tests_json.push(json);
        });
        tests_json = JSON.stringify(tests_json);

        let iav_source = {};
        if (this.$('.cw-iav-source').val() == 'url') {
            iav_source.url = this.$(".cw-iav-url").val();
            iav_source.external = true;
        } else {
            iav_source.url = this.$('.cw-iav-video-file option:selected').attr('file_url');
            iav_source.file_id = this.$('.cw-iav-video-file option:selected').attr('file_id');
            iav_source.file_name = this.$('.cw-iav-video-file option:selected').attr('file_name');
            iav_source.external = false;
        }
        iav_source = JSON.stringify(iav_source);

        helper
            .callHandler(this.model.id, "save", {iav_source: iav_source, iav_overlays: overlay_json, iav_stops: stop_json, iav_tests: tests_json, assignment_id: $assignment_id}) 
            .then(
                // success
                function () {
                    jQuery(event.target).addClass("accept");
                    $view.switchBack();
                },

                // error
                function (error) {
                    var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                    alert(errorMessage);
                    console.log(errorMessage, arguments);
                });
    },

    overlayPreview() {
        var $view = this;
        var $overlay_items = $view.$('.cw-iav-overlay-edit-item').not('.cw-iav-overlay-edit-item.item-default');
        var overlay_json = [];
        $overlay_items.each(function(index){
            var json = {
                "id" : $(this).data('overlayid'),
                "start" : $(this).find('input[name="cw-iav-start"]').val(),
                "end" : $(this).find('input[name="cw-iav-end"]').val(),
                "type" : $(this).find('select[name="cw-iav-type"]').val(),
                "position" : $(this).find('select[name="cw-iav-position"]').val(),
                "color" : $(this).find('select[name="cw-iav-color"]').val(),
                "title" : $(this).find('input[name="cw-iav-overlay-title"]').val(),
                "content" : $(this).find('textarea[name="cw-iav-content"]').val()
            };
            overlay_json.push(json);
        });
        $view.$('.overlay-json').val(JSON.stringify(overlay_json));
        $view.$('.cw-iav-overlay-content').not('.cw-iav-overlay-content-default').remove();
        $view.addOverlays(JSON.parse($view.$('.overlay-json').val()));
        var overlayid = $view.$('.cw-iav-overlay-edit-item.active-item').data('overlayid');
        $view.$('.overlay-list-item[data-overlayid="'+overlayid+'"]').trigger( "click" );
    },

    stopPreview() {
        var $view = this;
        var $stop_items = $view.$('.cw-iav-stop-edit-item').not('.cw-iav-stop-edit-item.item-default');
        var stop_json = [];
        $stop_items.each(function(index){
            var json = {
                "id" : $(this).data('stopid'),
                "start" : $(this).find('input[name="cw-iav-start"]').val(),
                "end" : $(this).find('input[name="cw-iav-end"]').val(),
                "type" : $(this).find('select[name="cw-iav-type"]').val(),
                "position" : $(this).find('select[name="cw-iav-position"]').val(),
                "color" : $(this).find('select[name="cw-iav-color"]').val(),
                "title" : $(this).find('input[name="cw-iav-stop-title"]').val(),
                "content" : $(this).find('textarea[name="cw-iav-content"]').val()
            };
            stop_json.push(json);
        });
        $view.$('.stop-json').val(JSON.stringify(stop_json));
        $view.$('.cw-iav-stop-content').not('.cw-iav-stop-content-default').remove();
        $view.addStops(JSON.parse($view.$('.stop-json').val()));
        var stopid = $view.$('.cw-iav-stop-edit-item.active-item').data('stopid');
        $view.$('.cw-iav-stop-content').hide();
        $view.$('.cw-iav-stop-content[data-stopid="'+stopid+'"]').show();
    },

    videoPreview() {
        var $player = this.$('.cw-iav-player');
        this.$('.cw-iav-overlay-edit-item').hide().removeClass('active-item');
        if (this.$('.cw-iav-source').val() == 'url') {
            $player.find('source').attr('src' , this.$('.cw-iav-url').val());
        } else {
            $player.find('source').attr('src' , this.$('.cw-iav-video-file option:selected').attr('file_url'));
        }
        $player.get(0).load();
        this.$('.cw-iav-wrapper').show();
        this.$('.cw-iav-controls').show();
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
            range: true,
            min: 0,
            max: $duration,
            values: [0, $duration],
            slide( event, ui ) {
                $player.prop('currentTime',ui.values[0]);
                $time.html($view.displayTimer(ui.values[0], $duration));
                $view.updateTimeSelection(ui.values);
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
        var overlay_data =  $view.$('.overlay-json').val();
        if (overlay_data != '') {
            var overlay_json =  JSON.parse(overlay_data);
            $view.addOverlays(overlay_json);
        }
        var stop_data =  $view.$('.stop-json').val();
        if (stop_data != '') {
            var stop_data =  JSON.parse(stop_data);
            $view.addStops(stop_data);
        }

        $player_element.addEventListener('timeupdate',function () {
            var $current = parseInt($player.prop('currentTime'));
            $range.slider('values', 0, $current);
            $time.html($view.displayTimer($current, $duration));
            $view.interactionEvent($current);
            $view.updateTimeSelection($range.slider('values'));
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

    updateTimeInput(event) {
        var $view = this,
            $range = $view.$('.cw-iav-range'),
            name = $(event.currentTarget).attr('name'),
            time = $(event.currentTarget).siblings('.'+name+'-readable');
        time.text($view.seconds2time($(event.currentTarget).val()));
        $view.$('.active-item').find('input[name="cw-iav-start"]').prop('max', $range.slider('values', 1));
        $view.$('.active-item').find('input[name="cw-iav-end"]').prop('min', $range.slider('values', 0));

        if (name == 'cw-iav-moment') {
            $range.slider('values', 0, $(event.currentTarget).val());
            $range.slider('values', 1, $range.slider('option', 'max'));
        } else {
            var pos = (name == 'cw-iav-start' ?  0 : 1);
            $range.slider('values', pos, $(event.currentTarget).val());
        }

        $view.$('.cw-iav-time').text($view.displayTimer($range.slider('values', 0), $range.slider('option','max')));
    },

    updateOverlayTitle(event) {
        var id = $(event.currentTarget).parents('.cw-iav-overlay-edit-item').data('overlayid');
        this.$('.overlay-list-item[data-overlayid="'+id+'"]').text($(event.currentTarget).val());
    },

    updateStopTitle(event) {
        var id = $(event.currentTarget).parents('.cw-iav-stop-edit-item').data('stopid');
        this.$('.stop-list-item[data-stopid="'+id+'"]').text($(event.currentTarget).val());
    },

    interactionEvent($time) {
        var $view = this;
        // parse json here in case of changing data by preview functions
        var overlay_data =  $view.$('.overlay-json').val();
        if (overlay_data != '') {
            var overlay_json =  JSON.parse(overlay_data);
            for (var i in overlay_json) {
                var $overlay = $view.$('.cw-iav-overlay-content');
                if (($time >= overlay_json[i].start ) && ($time <= overlay_json[i].end)) {
                    $overlay.filter('[data-overlayid='+overlay_json[i].id+']').show();
                } else {
                    $overlay.filter('[data-overlayid='+overlay_json[i].id+']').hide();
                }
            }
        }
        var stop_data =  $view.$('.stop-json').val();
        if (stop_data != '') {
            var stop_json =  JSON.parse(stop_data);
            for (var i in stop_json) {
                var $stop = $view.$('.cw-iav-stop-content');
                if ($time == stop_json[i].moment ) {
                    $stop.filter('[data-stopid='+stop_json[i].id+']').show();
                } else {
                    $stop.filter('[data-stopid='+stop_json[i].id+']').hide();
                }
            }
        }

        var test_data =  $view.$('.test-json').val();
        if (test_data != '') {
            var test_data =  JSON.parse(test_data);
            for (var i in test_data) {
                var $test = $view.$('.cw-iav-test-content');
                if ($time == test_data[i].moment ) {
                    $test.filter('[data-testid='+test_data[i].test_id+']').show();
                } else {
                    $test.filter('[data-testid='+test_data[i].test_id+']').hide();
                }
            }
        }
    },

    addOverlays(overlay_json) {
        var $view = this;
        var $overlay_wrapper = $view.$('.cw-iav-overlay-wrapper');
        var html = "";
        for (var i in overlay_json) {
            var $item = $view.$('.cw-iav-overlay-content-default').clone();
            $item.removeClass('cw-iav-overlay-content-default').addClass(overlay_json[i].position).addClass(overlay_json[i].type).addClass(overlay_json[i].color);
            $item.attr('data-overlayid', overlay_json[i].id);
            $item.find('.cw-iav-overlay-content-title').html(overlay_json[i].title);
            $item.find('.cw-iav-overlay-content-text').html(overlay_json[i].content);
            $($item).appendTo($overlay_wrapper);
        }
        $view.$('.cw-iav-overlay-content').hide();
    },

    addStops(stop_json) {
        var $view = this;
        var $stop_wrapper = $view.$('.cw-iav-stop-wrapper');
        var html = "";
        for (var i in stop_json) {
            var $item = $view.$('.cw-iav-stop-content-default').clone();
            $item.removeClass('cw-iav-stop-content-default').addClass(stop_json[i].position).addClass(stop_json[i].type).addClass(stop_json[i].color);
            $item.attr('data-stopid', stop_json[i].id);
            $item.find('.cw-iav-stop-content-title').html(stop_json[i].title);
            $item.find('.cw-iav-stop-content-text').html(stop_json[i].content);
            $($item).appendTo($stop_wrapper);
        }
        $view.$('.cw-iav-stop-content').hide();
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
    },

    editOverlayItem(event) {
        var $view = this,
            $player = $view.$('.cw-iav-player'),
            $player_element = $player[0],
            $range = $view.$('.cw-iav-range'),
            item_id = event.currentTarget.attributes.getNamedItem('data-overlayid').value;
        $('.overlay-list-item').removeClass('active-list-item');
        $(event.currentTarget).addClass('active-list-item');
        $view.$('.cw-iav-overlay-edit-item').hide();
        $view.$('.cw-iav-overlay-edit-item').removeClass('active-item');
        var $active_item = $view.$('.cw-iav-overlay-edit-item.item-'+item_id);
        $active_item.show();
        $active_item.addClass('active-item');
        $range.slider('values', 0, $active_item.find('input[name="cw-iav-start"]').val());
        $range.slider('values', 1, $active_item.find('input[name="cw-iav-end"]').val());
        $player_element.currentTime = $active_item.find('input[name="cw-iav-start"]').val();
    },

    editStopItem(event) {
        var $view = this,
            $player = $view.$('.cw-iav-player'),
            $player_element = $player[0],
            $range = $view.$('.cw-iav-range'),
            item_id = event.currentTarget.attributes.getNamedItem('data-stopid').value;
        $('.stop-list-item').removeClass('active-list-item');
        $(event.currentTarget).addClass('active-list-item');
        $view.$('.cw-iav-stop-edit-item').hide();
        $view.$('.cw-iav-stop-edit-item').removeClass('active-item');
        var $active_item = $view.$('.cw-iav-stop-edit-item.item-'+item_id);
        $active_item.show();
        $active_item.addClass('active-item');
        $range.slider('values', 0, $active_item.find('input[name="cw-iav-moment"]').val());
        $range.slider('values', 1, $range.slider('option', 'max'));
        $player_element.currentTime = $active_item.find('input[name="cw-iav-moment"]').val();
        $view.$('.cw-iav-stop-content').hide();
        $view.$('.cw-iav-stop-content[data-stopid="'+item_id+'"]').show();
    }, 

    editTestItem(event) {
        var $view = this,
            $player = $view.$('.cw-iav-player'),
            $player_element = $player[0],
            $range = $view.$('.cw-iav-range'),
            item_id = event.currentTarget.attributes.getNamedItem('data-testid').value;
        $view.$('.cw-iav-test-content').css('z-index', '0');
        $view.$('.cw-iav-test-content[data-testid="'+item_id+'"]').css('z-index', '42').show();
        $view.$('.cw-iav-test-edit-item').hide();
        $view.$('.cw-iav-test-edit-item[data-testid='+item_id+']').show();
        $('.test-list-item').removeClass('active-list-item');
        $(event.currentTarget).addClass('active-list-item');

        var $active_item = $view.$('.cw-iav-test-edit-item[data-testid="'+item_id+'"]');
        $view.$('.cw-iav-test-edit-item').removeClass('active-item');
        $active_item.addClass('active-item');
        $range.slider('values', 0, $active_item.find('input[name="cw-iav-moment"]').val());
        $range.slider('values', 1, $range.slider('option', 'max'));
        $player_element.currentTime = $active_item.find('input[name="cw-iav-moment"]').val();
        $(window).trigger('resize');
    },

    updateTimeSelection(values) {
        var $active_overlay_item = this.$('.cw-iav-overlay-edit-item.active-item');
        if($active_overlay_item) {
            $active_overlay_item.find('input[name="cw-iav-start"]').val(values[0]).trigger('change');
            $active_overlay_item.find('input[name="cw-iav-end"]').val(values[1]).trigger('change');
        }
        var $active_stop_item = this.$('.cw-iav-stop-edit-item.active-item');
        if($active_stop_item) {
            $active_stop_item.find('input[name="cw-iav-moment"]').val(values[0]).trigger('change');
        }
        var $active_test_item = this.$('.cw-iav-test-edit-item.active-item');
        if($active_test_item) {
            $active_test_item.find('input[name="cw-iav-moment"]').val(values[0]).trigger('change');
        }
    },

    addItem(event) {
        var type = '';
        if($(event.currentTarget).hasClass('overlay-adder')) {
            type = 'overlay';
        }
        if($(event.currentTarget).hasClass('stop-adder')){
            type = 'stop';
        }
        if (type == ''){
            return;
        }
        var $view = this,
            $adder = $view.$('.'+type+'-adder').parents('li'),
            $edit = $view.$('.cw-iav-'+type+'-edit-wrapper'),
            $item = $view.$('.cw-iav-'+type+'-edit-item.item-default').clone(),
            id = $view.$('.cw-iav-'+type+'-edit-item').not('.item-default').length;
        $('.'+type+'-list-item').removeClass('active-list-item');
        $('.cw-iav-'+type+'-edit-item').removeClass('active-item');
        $item.addClass('active-item');
        $view.$('.cw-iav-'+type+'-edit-item').hide();
        $item.attr('data-'+type+'id', id);
        $item.find('input[name="cw-iav-'+type+'-title"]').val('Titel');
        $item.removeClass('item-default').addClass('item-'+id);
        $item.find('button.'+type+'-remover').attr('data-'+type+'id',id);
        var html = '<li> <button class="button '+type+'-list-item active-item" data-'+type+'id="'+ id +'">Titel</button></li>'
        $(html).insertBefore($adder);
        $($item).appendTo($edit).show();
        $view.$('.'+type+'-list-item[data-'+type+'id="'+id+'"]').trigger("click");
    },

    removeItem(event) {
        var view = this;
        var type = '';
        var name = '';
        if($(event.currentTarget).hasClass('overlay-remover')) {
            type = 'overlay';
            name = 'Einblendung';
        }
        if($(event.currentTarget).hasClass('stop-remover')){
            type = 'stop';
            name = 'Haltepunkt';
        }
        if (type == ''){
            return;
        }
        var html = '<div id="dialog"><p>Hiermit löschen Sie die Einblendung. Die Änderung wird erst mit dem speichern des Blockes wirksam.</p></div>';
        $(html).appendTo($(event.currentTarget));
        $('#dialog').dialog({
          resizable: false,
          height: "auto",
          width: 400,
          modal: true,
          title: name + " entfernen",
          buttons: {
            "Löschen": function() {
              $( this ).dialog( "close" );
                var item_id = event.currentTarget.attributes.getNamedItem('data-'+type+'id').value;
                view.$('.'+type+'-list-item.active-item[data-'+type+'id="'+item_id+'"]').parent().remove();
                view.$('.cw-iav-'+type+'-edit-item[data-'+type+'id="'+item_id+'"]').remove();
                var $prev_item = view.$('.'+type+'-list-item[data-'+type+'id="'+(item_id-1)+'"]');
                if($prev_item) {
                    $prev_item.trigger("click");
                }
            },
            "Abbrechen": function() {
              $( this ).dialog( "close" );
            }
          }
        });
    },

    disableItems(event) {
        var $view = this,
            $range = $view.$('.cw-iav-range');
        $view.$('.active-item').removeClass('active-item');
        $view.$('.active-list-item').removeClass('active-list-item');
        $view.$('.cw-iav-overlay-edit-item').hide();
        $view.$('.cw-iav-stop-edit-item').hide();
        $range.slider('option', 'values', [0, $range.slider('option', 'max')]);
    },

    getVipsTests(event) { 
        var $view = this;
        var $assignment_id = $view.$('select[name="assignment_id"]').val();
        $view.$('.cw-iav-test-item').not('.cw-iav-test-item-default').parent().remove();
        $view.$('.test-list-item').parent().remove();
        if ($assignment_id != '') {
            helper
                .callHandler(this.model.id, "getVipsTests", {assignment_id: $assignment_id}) 
                .then(
                    // success
                    function (response) {
                        $view.showVipsTests(response);
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not get vips tests: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    });
        }
    },

    showVipsTests(response) {
        var $view = this;
        var $test_list = $view.$('.cw-iav-tests-list');
        var $test_wrapper = $view.$('.cw-iav-test-edit-wrapper');
        var test_data =  $view.$('.test-json').val();
        var stored_assignment =  $view.$('.cw-iav-assignment-id-stored').val() == $view.$('select[name="assignment_id"]').val();
        $view.$('.cw-iav-test-content').hide();
        for (var i in response) {
            var exercise = jQuery.parseJSON(response[i]);
            var $button = '<li><button class="button test-list-item" data-testid="'+exercise.id+'">'+exercise.title+'</button></li>';
            $($button).appendTo($test_list);
            var $item = $view.$('.cw-iav-test-edit-item-default').clone();
            $item.removeClass('cw-iav-test-edit-item-default');
            $item.attr('data-testid', exercise.id);
            $item.find('.cw-iav-test-id').val(exercise.id);
            $item.find('.cw-iav-test-title').val(exercise.title);
            $($item).appendTo($test_wrapper).hide();
            if($view.$('.cw-iav-test-content[data-testid="'+exercise.id+'"]').length == 0) {$view.addTest(exercise.title, exercise.id);}
        }
        if (test_data != '') {
            var test_data = JSON.parse(test_data);
            for (var i in test_data) {
                var $item = $view.$('.cw-iav-test-edit-item[data-testid="'+test_data[i].test_id+'"]');
                $item.find('input[name="cw-iav-moment"]').val(test_data[i].moment).trigger('change');
                $item.find('input.cw-iav-test-use').prop( "checked", test_data[i].use_test);
            }
        }
    },

    addTest(title, id) {
        var $view = this;
        var $test_wrapper = $view.$('.cw-iav-test-wrapper');
        var html = "";
        var $item = $view.$('.cw-iav-test-content-default').clone();
        $item.removeClass('cw-iav-test-content-default');
        $item.attr('data-testid', id);
        $item.find('.cw-iav-test-content-title').html("Vips-Test: "+title);
        $item.find('.cw-iav-test-content-test-id').html("Test-Id: "+id);
        $($item).appendTo($test_wrapper);
    },

    toggleSource() {
        if (this.$('.cw-iav-source').val() == 'url') {
            this.$('.cw-iav-video-file').hide();
            this.$('.cw-iav-url').show();

        } else {
            this.$('.cw-iav-url').hide();
            this.$('.cw-iav-video-file').show();
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
    }
});

