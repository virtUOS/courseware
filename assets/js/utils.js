define({
    normalizeYouTubeLink: function (url) {
        // YouTube API Docs - https://developers.google.com/youtube/
        //
        // Discussion of valid YouTube video IDs
        // https://groups.google.com/forum/#!topic/youtube-api-gdata/maM-h-zKPZc
        //
        // examples for long URL, short URL, and embed URL:
        // http://www.youtube.com/watch?v=C3HFAyigqoY&feature=youtu.be
        // http://youtu.be/C3HFAyigqoY
        // //www.youtube.com/embed/C3HFAyigqoY
        //
        // examples for IDs with _ and - characters:
        // http://www.youtube.com/watch?v=k_wJsio68D4
        // http://www.youtube.com/watch?v=h-TPSylHrvE
        var
        videoId = '[\\w\\-]*',
        idQuery = 'v=(' + videoId + ')',
        queryName = '(?:[^=&;#]{2,}|[^=&;#v])',
        queryValue = '(?:=[^&;#]*)?',
        otherQueries = '(?:' + queryName + queryValue + '[&;])*',
        longLink = '(?:www\\.)?youtube\\.com\\/watch\\?' + otherQueries + idQuery,
        shortLink = 'youtu\\.be\\/(' + videoId + ')',
        youTubeLink = '^\\s*'       // ignore whitespace at beginning of line
        + '(?:https?:)?\\/\\/'  // URL scheme is optional
        + '(?:' + longLink + '|' + shortLink + ')',
        matches = url.match(new RegExp(youTubeLink));

        return matches ? (matches[1] || matches[2]) : null;
    },
    normalizeMatterhornLink: function (url) {
        // see https://opencast.jira.com/wiki/display/MH/Engage+URL+Parameters
        // http://someURL:8080/engage/ui/watch.html?id=someMediaPackageId
        // http://someURL:8080/engage/ui/embed.html?id=someMediaPackageId
        return url.replace('/engage/ui/watch.html?', '/engage/ui/embed.html?');
    },
    normalizeLink: function (url) {
        return url && this.normalizeMatterhornLink(this.normalizeYouTubeLink(url));
    },

    getYouTubeId: function(url) {
        if (url.length == 11) {
            return url;
        }
        var regExp = /^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?]*).*/;
        var match = url.match(regExp);

        if (match && match[7].length == 11) {
            return match[7];
        } else {
            return 'fehler';
        }
    },

    getUrl: function(view, videotype){
        var url = '';
        switch(videotype){
            case 'youtube':
                var id = view.$('#videosrc'),
                value = id.val(),
                youtubeid = this.getYouTubeId(value);
                url = this.buildYouTubeLink(youtubeid, view.$('#videostartmin').val(), view.$('#videostartsec').val(), view.$('#videoendmin').val(),view.$('#videoendsec').val(),view.$('#videoautostart').is(':checked'));
                id.val(youtubeid);
                break;
            case 'matterhorn':
                url = this.normalizeMatterhornLink(view.$('#videosrc').val());
		url = this.buildMatterhornLink(url, view.$('#videostartmin').val(), view.$('#videostartsec').val(), view.$('#videoautostart').is(':checked'), view.$('#videocontrols').is(':checked'));
                break;
            case 'url':
                url = view.$('#videosrc').val();
                break;
	    }

        return url;
    },

    getVideoType: function(url){
	var videotype = '';
	if(url.indexOf("youtube") != -1) videotype = "youtube";
	else if (url.indexOf("engage") != -1) videotype = "matterhorn";
	else videotype = "url";
	return videotype;
    },
    resetVideoData: function(view){
	view.$('#videosrc').val('');
	view.$('#videosettings input').val('').removeAttr('checked').removeAttr('selected').prop('disabled', false);
    },
    setVideoData: function(view, url, videotype){
	if ((videotype == 'youtube')&&(this.getVideoType(url) == 'youtube')) {
			view.$('#videocontrols').prop('disabled', true);
                        var youtubeid = url.slice(29).split("?",1);
                        view.$('#videosrc').val(youtubeid);
                        var start = url.slice(url.indexOf("start=")+6, url.length);
                        start = start.split("&", 1);
                        view.$('#videostartmin').val(parseInt(start/60));
                        view.$('#videostartsec').val(start%60);
                        var end = url.slice(url.indexOf("end=")+4, url.length);
                        view.$('#videoendmin').val(parseInt(end/60));
                        view.$('#videoendsec').val(end%60);
                        var autoplay = url.slice(url.indexOf("autoplay=")+9, url.length); 
			if(parseInt(autoplay) == 1) view.$('#videoautostart').attr("checked", '');
	}	
        if((videotype=='matterhorn')&&(this.getVideoType(url)== 'matterhorn')){
			var urlandid = url.split("&", 1);
			var autoplay = '', start = '', hidecontrols = '';
			view.$('#videosrc').val(urlandid);
			var urlArray = url.split("&");
			$.each(urlArray, function( index, value){
				if(value.indexOf('play') != -1)  autoplay = value.split('=')[1];
				if (value.indexOf('t=') != -1) start = value.split('=')[1];
				if(value.indexOf('hideControls') != -1) hidecontrols = value.split('=')[1];
			});
			console.log(autoplay);
			console.log(start);
			console.log(hidecontrols);
			if (autoplay == 'true') view.$('#videoautostart').attr("checked", '');
			if (hidecontrols == 'true') view.$('#videocontrols').attr("checked", '');
			if (start != ''){ 
				var start = start.split("m");
				view.$('#videostartmin').val(start[0]);
				view.$('#videostartsec').val(start[1].split("s",1)); 
			}
			view.$('#videoendmin').prop('disabled', true);
			view.$('#videoendsec').prop('disabled', true);
	}
        if((videotype == 'url')&&(this.getVideoType(url) == 'url')){
                        view.$('#videosrc').val(url);
			view.$('#videosettings input').prop('disabled', true);
        }

    },
    buildYouTubeLink: function(id, startmin, startsec, endmin, endsec, autoplay){

	var url =  'http://www.youtube.com/embed/'+id, start = 0, end = 0;
	if(startmin != '') start += parseInt(startmin)*60;
	if(startsec != '') start += parseInt(startsec);
	if(endmin != '') end += parseInt(endmin)*60;
	if(endsec != '') end  += parseInt(endsec);
	if (start != 0){
		url += '?start='+start;
		if ((end != 0)&&(start < end)) url += '&end='+end;
	}else{
		if (end != 0) url += '?end='+end;
	}
	if(autoplay) {
		if((start != 0)||(end != 0))
		url += '&autoplay=1';
		else url += '?autoplay=1';
	}
	return url;
    },
    buildMatterhornLink: function(url, startmin, startsec, autoplay, controls){
	var start = '';
	if(startmin != '') start += startmin + 'm';
        if(startsec != '') start += startsec + 's';
        if (start != '') url += '&t='+start;
	if (autoplay)  url += '&play=true'; 
	if (controls) url += '&hideControls=true'; else url +='&hideControls=false';
        return url;

    },
    showPreview: function(view, url){
	var iframe = view.$('iframe');
	iframe.attr('src', url);
    }
	
});
