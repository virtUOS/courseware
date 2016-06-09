define({
    getYouTubeId: function (url) {
        if (url.length == 11) {
            return url;
        }

        var regExp = /^.*(youtu.be\/|v\/|embed\/|watch\?|youtube.com\/|user\|watch\?|feature=player_embedded\&|\/[^#]*#([^\/]*?\/)*)\??v?=?([^#\&\?]*).*/,
        match = url.match(regExp);

        if (match && match[3].length == 11) {
            return match[3];
        } else {
            return false;
        }
    },

    getUrl: function (view, videotype) {
        var url = '', message = '';
        switch (videotype) {
            case 'youtube':
                var id = view.$el.find('.videosrc'), value = id.val();

                if (this.getYouTubeId(value)) {
                    var youtubeid = this.getYouTubeId(value);
                } else {
                    message = 'Fehlerhafte youtube ID. Wert wurde zurückgesetzt.';
                    break;
                }

                url = this.buildYouTubeLink(youtubeid, view.$el.find('.videostartmin').val(), view.$el.find('.videostartsec').val(), view.$el.find('.videoendmin').val(),view.$el.find('.videoendsec').val(),view.$el.find('.videoautostart').is(':checked'));
                id.val(youtubeid);
                break;
            case 'matterhorn':
                var matterhornurl = view.$el.find('.videosrc').val();

                if (matterhornurl.indexOf('?id=') == -1 ) {
                    message = 'Keine Matterhorn ID übergeben. Wert wurde zurückgesetzt.';
                    break;
                }

                if (matterhornurl.indexOf('/engage/ui/watch.html?') != -1) {
                    matterhornurl = matterhornurl.replace('/engage/ui/watch.html?', '/engage/ui/embed.html?');
                    message = 'Matterhorn URL wurde berichtigt.';
                }

                matterhornurl = matterhornurl.split('&')[0];
                url = this.buildMatterhornLink(matterhornurl, view.$el.find('.videostartmin').val(), view.$el.find('.videostartsec').val(), view.$el.find('.videoautostart').is(':checked'), view.$el.find('.videocontrols').is(':checked'));
                view.$el.find('.videosrc').val(matterhornurl);
                break;
            case 'dfb':
                url = "//tv.dfb.de/player_frame.php?view=" + view.$('.videosrc').val();
                break;
            case 'url':
                url = view.$el.find('.videosrc').val();
                if (url.match(/youtube\.(com|de)/)) {                           // do we have a youtube url?
                    var youtubeid = url.split('v=')[1];
                    var ampersandPosition = youtubeid.indexOf('&');
                    if (ampersandPosition != -1) {
                        youtubeid = video_id.substring(0, ampersandPosition);
                    }

                    // generate usable youtube url and set the appropriate values in the view
                    url = this.buildYouTubeLink(youtubeid, view.$el.find('.videostartmin').val(), view.$el.find('.videostartsec').val(), view.$el.find('.videoendmin').val(),view.$el.find('.videoendsec').val(),view.$el.find('.videoautostart').is(':checked'));
                    view.$el.find('.videosrc').val(youtubeid);
                    view.$el.find('.videotype').val('youtube');
                }
                break;
        }

        if (message != '') {
            view.$('.status').html(message).css('color', '#ff0000').fadeIn().delay(3000).fadeOut();
        } else {
            view.$('.status').html(url).css('color', '#24437c').fadeIn().delay(3000).fadeOut();
        }

        return url;
    },

    getVideoType: function (url) {
        var videotype = '';
        if (url.indexOf("youtube") != -1) {
            videotype = "youtube";
        } else if (url.indexOf("engage") != -1) {
            videotype = "matterhorn";
        } else if (url.indexOf("tv.dfb.de") != -1) {
            videotype = "dfb";
        } else {
            videotype = "url";
        }

        return videotype;
    },

    resetVideoData: function (view) {
        view.$el.find('.videosrc').val('');
        view.$el.find('.videosettings input:not([name="videoaspect"])').val('').removeAttr('checked').removeAttr('selected').prop('disabled', false);
    },

    setVideoData: function (view, url, videotype) {
        view.$el.find('.videosettings input').prop('disabled', false);

        if (videotype == 'youtube') {
            view.$el.find('.videocontrols').prop('disabled', true);

            if(view.$('.video-wrapper').hasClass('aspect-43')) {
                view.$el.find('.videoaspect43').prop('checked', true);
            } else {
                view.$el.find('.videoaspect169').prop('checked', true);
            }

            if (this.getVideoType(url) == 'youtube') {
                var youtubeid = url.slice(24).split("?",1);
                view.$el.find('.videosrc').val(youtubeid);
                var start = url.slice(url.indexOf("start=")+6, url.length);
                start = start.split("&", 1);
                view.$el.find('.videostartmin').val(parseInt(start/60));
                view.$el.find('.videostartsec').val(start%60);
                var end = url.slice(url.indexOf("end=")+4, url.length);
                view.$el.find('.videoendmin').val(parseInt(end/60));
                view.$el.find('.videoendsec').val(end%60);
                var autoplay = url.slice(url.indexOf("autoplay=")+9, url.length);

                if (parseInt(autoplay) == 1) {
                    view.$el.find('.videoautostart').attr("checked", '');
                }
            }
        }

        if (videotype == 'matterhorn') {
            view.$el.find('.videoendmin').prop('disabled', true);
            view.$el.find('.videoendsec').prop('disabled', true);

            if (this.getVideoType(url) == 'matterhorn') {
                var urlandid = url.split("&", 1);
                var autoplay = '', start = '', hidecontrols = '';
                view.$el.find('.videosrc').val(urlandid);
                var urlArray = url.split("&");
                $.each(urlArray, function ( index, value) {
                    if (value.indexOf('play') != -1) {
                        autoplay = value.split('=')[1];
                    }

                    if (value.indexOf('t=') != -1) {
                        start = value.split('=')[1];
                    }

                    if (value.indexOf('hideControls') != -1) {
                        hidecontrols = value.split('=')[1];
                    }
                });

                if (autoplay == 'true') {
                    view.$el.find('.videoautostart').attr("checked", '');
                }

                if (hidecontrols == 'true') {
                    view.$el.find('.videocontrols').attr("checked", '');
                }

                if (start != '') {
                    var start = start.split("m");
                    view.$el.find('.videostartmin').val(start[0]);
                    view.$el.find('.videostartsec').val(start[1].split("s",1));
                }
            }
        }

        if (videotype == 'dfb') {
            view.$('.videosettings input').prop('disabled', true);
            view.$('.videoaspect169').prop('checked', true);
            if (this.getVideoType(url) == 'url') {
                view.$('.videosrc').val(url);
            }
        }

        if (videotype == 'url') {
            view.$el.find('.videosettings input:not([name="videoaspect"])').prop('disabled', true);

            if (this.getVideoType(url) == 'url') {
                view.$el.find('.videosrc').val(url);
            }
        }
    },

    buildYouTubeLink: function (id, startmin, startsec, endmin, endsec, autoplay) {
        var url = '//www.youtube.com/embed/'+id, start = 0, end = 0;    // ommit protocol to prevent http/https-problems

        if (startmin != '') {
            start += parseInt(startmin)*60;
        }

        if (startsec != '') {
            start += parseInt(startsec);
        }

        if (endmin != '') {
            end += parseInt(endmin)*60;
        }

        if (endsec != '') {
            end  += parseInt(endsec);
        }

        if (start != 0) {
            url += '?start='+start;
            if (end != 0 && start < end) {
                url += '&end=' + end;
            }
        } else if (end != 0) {
            url += '?end='+end;
        }

        if (autoplay) {
            if (start != 0 ||end != 0) {
                url += '&autoplay=1';
            } else {
                url += '?autoplay=1';
            }
        }
        return url;
    },

    buildMatterhornLink: function (url, startmin, startsec, autoplay, controls) {
        var start = '';

        if (startmin != '') {
            start += startmin + 'm';
        }

        if (startsec != '') {
            start += startsec + 's';
        }

        if (start != '') {
            url += '&t='+start;
        }

        if (autoplay) {
            url += '&play=true';
        }

        if (controls) {
            url += '&hideControls=true';
        } else {
            url += '&hideControls=false';
        }

        return url;
    },

    showPreview: function (view, url) {
        view.$('iframe').attr('src', url);
    },

});
