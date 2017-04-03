define(['assets/js/author_view', 'assets/js/url'], function (
    AuthorView, helper
) {
    'use strict';
    return AuthorView.extend({
        events: {
            "click button[name=save]": "saveVideo",
            "click button[name=cancel]": "switchBack",
            "change select.videotype": "selection",
            "click button[name=preview]": "preview",
            "click button[name=videotimereset]": "videotimereset",
            "click button[name=addmediatype]": "addmediatype",
        },
        initialize: function(options) {
            Backbone.on('beforemodeswitch', this.onModeSwitch, this);
            Backbone.on('beforenavigate', this.onNavigate, this);
        },
        onNavigate: function(event) {
            if(!$("section .block-content button[name=save]").length) return;
            if(event.isUserInputHandled) return;
                event.isUserInputHandled = true;
                Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
        },
        onModeSwitch: function (toView, event) {
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
        render: function() {
            return this;
        },
        postRender: function() {
            var url = this.$el.find('.videourl').val();
            var videotype = this.getVideoType(url);
            this.showPreview(this, url);
            this.$el.find(".videotype option[value="+videotype+"]").attr('selected', true);
            this.selection();
            
        },
        saveVideo: function() {
            this.preview();
            var view = this;
            var videotype = this.$el.find('.videotype').val();
            var status = this.$('.status');
            var aspect = this.$('input[name="videoaspect"]:checked').val();
            var videoTitle = this.$el.find(".videotitle").val();
            if (videotype == "webvideo") {
                var url = "";
                var webvideosettings = "controls ";
                var webvideo = new Array();
                this.$(".videosource-webvideo > .webvideo").each(function(){
                    var src = $(this).find('.webvideosrc').val();
                    var type = $(this).find('.webvideosrc-mediatype').val();
                    var query = $(this).find('.webvideosrc-mediaquery').val();
                    var media = "", attr = "";
                    switch (query) {
                        case "normal":
                            media = "";
                            break;
                        case "large":
                            media = "screen and (min-device-width:801px)";
                            break;
                        case "small":
                            media = "screen and (max-device-width:800px)";
                            break;
                    }
                    
                    if (src != "") { 
                        webvideo.push({"src": src, "type": type, "query": query, "media": media, "attr": attr});
                    }
                });
                if (view.$el.find('.videoautostart').is(':checked')) {webvideosettings += "autoplay ";}
                webvideo = JSON.stringify(webvideo);
            } else {
                var url = this.$el.find('.videourl').val()
                var webvideo = "";
                var webvideosettings = "";
            }

            status.text('Speichere Änderungen...');
            var view = this;
            helper.callHandler(this.model.id, 'save', { url: url, webvideo: webvideo, webvideosettings: webvideosettings,videoTitle: videoTitle, aspect: aspect}).then(
                function() {
                    status.text('Änderungen wurden gespeichert.');
                    view.switchBack();
                },
                function (error) {
                    status.text('Fehler beim Speichern: '+jQuery.parseJSON(error.responseText).reason);
                }
            ).done();
        },
        
        /*  UTILS   */
        preview: function() {
            var videourl = this.$el.find('.videourl');
            var videotype = this.$el.find('.videotype').val();
            var url = this.getUrl(this, videotype);
            var aspect = this.$('input[name="videoaspect"]:checked').val();
            if (videotype != "webvideo") { 
                videourl.val(url); 
            }
            this.showPreview(this, url);
            this.$('.video-wrapper').attr('class', 'video-wrapper '+aspect);
            
        },
        selection: function() {
            
            var videotype = this.$el.find('.videotype').val();
            this.$el.find(".videosource").hide();
            this.$el.find(".videotimer").hide();
            this.$el.find("iframe").hide();
            this.$el.find("video").hide();
            this.$el.find(".videocontrols-wrapper").hide();
            this.$el.find(".videoautostart-wrapper").show();
            this.$el.find(".videosettings-header").show();
            switch (videotype) {
                case "webvideo":
                    this.$el.find(".videosource-webvideo").show();
                    this.$el.find("video").show();
                    break;
                case "youtube":
                    this.$el.find(".videosource-url").show();
                    this.$el.find(".videotimer").show();
                    this.$el.find("iframe").show();
                    this.$el.find('.videosrcname').html('YouTube ID');
                    break;
                case "matterhorn":
                    this.$el.find(".videosource-url").show();
                    this.$el.find("iframe").show();
                    //this.$el.find(".videocontrols-wrapper").show();
                    this.$el.find(".videoaspect").hide();
                    this.$el.find('.videosrcname').html('URL');
                    break;
                case "url":
                    this.$el.find(".videosource-url").show();
                    this.$el.find("iframe").show();
                    this.$el.find('.videosrcname').html('URL');
                    this.$el.find(".videoautostart-wrapper").hide();
                    this.$el.find(".videosettings-header").hide();
                    
                    break;
            }
            this.resetVideoData(this);
            this.setVideoData(this, this.$el.find('.videourl').val(), videotype);

        },
        videotimereset: function() {
            this.$el.find('.videostartmin').val("");
            this.$el.find('.videostartsec').val("");
            this.$el.find('.videoendmin').val("");
            this.$el.find('.videoendsec').val("");
        },
        addmediatype: function(event, src, type, query) {
            var $addmediatype = this.$(".addmediatype");
            var $webvideo = this.$(".videosource-webvideo > .webvideo").first().clone();
            $webvideo.find(".webvideosrc").val(src);
            $webvideo.find(".webvideosrc-mediatype option[value='"+type+"']").prop('selected', true);
            $webvideo.find(".webvideosrc-mediaquery option[value='"+query+"']").prop('selected', true);
            $webvideo.insertBefore($addmediatype);
        },
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
                    /*
                    if (matterhornurl.indexOf('/engage/ui/watch.html?') != -1) {
                        matterhornurl = matterhornurl.replace('/engage/ui/watch.html?', '/engage/ui/embed.html?');
                        message = 'Matterhorn URL wurde berichtigt.';
                    }
                    */
                    matterhornurl = matterhornurl.split('&')[0];
                    url = this.buildMatterhornLink(matterhornurl, view.$el.find('.videostartmin').val(), view.$el.find('.videostartsec').val(), view.$el.find('.videoautostart').is(':checked'), view.$el.find('.videocontrols').is(':checked'));
                    view.$el.find('.videosrc').val(matterhornurl);
                    break;
                case 'url':
                    url = view.$el.find('.videosrc').val();
                    // do we have a youtube url?
                    if (url.match(/youtube\.(com|de)/)) {
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
            if (url == '') {
                return "webvideo";
            }
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
            view.$el.find('.videosrc').val('');
            view.$el.find('.webvideosrc').val('');
            view.$el.find('.webvideosrc-mediatype option').prop('selected', false);
            view.$el.find('.webvideosrc-mediaquery option').prop('selected', false);
            view.$el.find('.webvideo').not(':first').remove();;
            view.$el.find('.videosettings input:not([name="videoaspect"])').val('').removeAttr('checked').removeAttr('selected').prop('disabled', false);
        },
        setVideoData: function (view, url, videotype) {
            view.$el.find('.videosettings input').prop('disabled', false);
            if ( (url == "")||(videotype == "") ) {
                return;
            }
            switch (videotype) {
                    case "webvideo":
                        var webvideodata = view.$el.find('.webvideodata').val();
                        var webvideosettingsdata = view.$el.find('.webvideosettingsdata').val();
                        
                        if (webvideodata != "[]") {
                            webvideodata = $.parseJSON(webvideodata);
                            view.$el.find("video").attr("src", webvideodata[0].src);
                            $.each(webvideodata, function(key, value){
                                view.addmediatype(null, value.src, value.type, value.query);
                            });
                            view.$(".videosource-webvideo > .webvideo").first().remove();
                        }
                        if (webvideosettingsdata.indexOf("autoplay") > -1) {
                            view.$el.find('.videoautostart').prop("checked", true);
                        }
                        break;
                    case "youtube":
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
                            end = end.split("&", 1);
                            view.$el.find('.videoendmin').val(parseInt(end/60));
                            view.$el.find('.videoendsec').val(end%60);
                            var autoplay = url.slice(url.indexOf("autoplay=")+9, url.length);
                            if (parseInt(autoplay) == 1) {
                                view.$el.find('.videoautostart').prop("checked", true);
                            }
                        }
                        break;
                    case "matterhorn":
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
                            /*
                            if (hidecontrols == 'true') {
                                view.$el.find('.videocontrols').attr("checked", '');
                            }
                            */
                            if (start != '') {
                                var start = start.split("m");
                                view.$el.find('.videostartmin').val(start[0]);
                                view.$el.find('.videostartsec').val(start[1].split("s",1));
                            }
                        }
                        break;
                    case "url":
                        if (this.getVideoType(url) == 'url') {
                            view.$el.find('.videosrc').val(url);
                        }
                        break;
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
                url += '&autoplay=true';
            }
            /*
            if (controls) {
                url += '&hideControls=true';
            } else {
                url += '&hideControls=false';
            }
            */
            return url+= '&mode=embed';
        },
        showPreview: function (view, url) {
            view.$('iframe').attr('src', url);
            console.log(view.$(".webvideosrc").val());
            view.$("video").attr("src", view.$(".webvideosrc").val());
        },
        
        /*    UTILS END    */

    });
});
