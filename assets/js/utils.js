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
                var id = view.$('#videosrc'), value = id.val();

                if (this.getYouTubeId(value)) {
                    var youtubeid = this.getYouTubeId(value);
                } else {
                    message = 'Fehlerhafte youtube ID. Wert wurde zurückgesetzt.';
                    break;
                }

                url = this.buildYouTubeLink(youtubeid, view.$('#videostartmin').val(), view.$('#videostartsec').val(), view.$('#videoendmin').val(),view.$('#videoendsec').val(),view.$('#videoautostart').is(':checked'));
                id.val(youtubeid);
                break;
            case 'matterhorn':
                var matterhornurl = view.$('#videosrc').val();

                if (matterhornurl.indexOf('?id=') == -1 ) {
                    message = 'Keine Matterhorn ID übergeben. Wert wurde zurückgesetzt.';
                    break;
                }

                if (matterhornurl.indexOf('/engage/ui/watch.html?') != -1) {
                    matterhornurl = matterhornurl.replace('/engage/ui/watch.html?', '/engage/ui/embed.html?');
                    message = 'Matterhorn URL wurde berichtigt.';
                }

                matterhornurl = matterhornurl.split('&')[0];
                url = this.buildMatterhornLink(matterhornurl, view.$('#videostartmin').val(), view.$('#videostartsec').val(), view.$('#videoautostart').is(':checked'), view.$('#videocontrols').is(':checked'));
                view.$('#videosrc').val(matterhornurl);
                break;
            case 'url':
                url = view.$('#videosrc').val();
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
        } else {
            videotype = "url";
        }

        return videotype;
    },

    resetVideoData: function (view) {
        view.$('#videosrc').val('');
        view.$('#videosettings input:not([name="videoaspect"])').val('').removeAttr('checked').removeAttr('selected').prop('disabled', false);
    },

    setVideoData: function (view, url, videotype) {
        view.$('#videosettings input').prop('disabled', false);
        
        if (videotype == 'youtube') {
            view.$('#videocontrols').prop('disabled', true);
            
            if(view.$('.video-wrapper').hasClass('aspect-43')) view.$('#videoaspect43').prop('checked', true);
            else view.$('#videoaspect169').prop('checked', true);
            
            if (this.getVideoType(url) == 'youtube') {
                var youtubeid = url.slice(24).split("?",1);
                view.$('#videosrc').val(youtubeid);
                var start = url.slice(url.indexOf("start=")+6, url.length);
                start = start.split("&", 1);
                view.$('#videostartmin').val(parseInt(start/60));
                view.$('#videostartsec').val(start%60);
                var end = url.slice(url.indexOf("end=")+4, url.length);
                view.$('#videoendmin').val(parseInt(end/60));
                view.$('#videoendsec').val(end%60);
                var autoplay = url.slice(url.indexOf("autoplay=")+9, url.length);

                if (parseInt(autoplay) == 1) {
                    view.$('#videoautostart').attr("checked", '');
                }
            }
        }

        if (videotype == 'matterhorn') {
            view.$('#videoendmin').prop('disabled', true);
            view.$('#videoendsec').prop('disabled', true);

            if (this.getVideoType(url) == 'matterhorn') {
                var urlandid = url.split("&", 1);
                var autoplay = '', start = '', hidecontrols = '';
                view.$('#videosrc').val(urlandid);
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
                    view.$('#videoautostart').attr("checked", '');
                }

                if (hidecontrols == 'true') {
                    view.$('#videocontrols').attr("checked", '');
                }

                if (start != '') {
                    var start = start.split("m");
                    view.$('#videostartmin').val(start[0]);
                    view.$('#videostartsec').val(start[1].split("s",1));
                }
            }
        }

        if (videotype == 'url') {
            view.$('#videosettings input:not([name="videoaspect"])').prop('disabled', true);

            if (this.getVideoType(url) == 'url') {
                view.$('#videosrc').val(url);
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
