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
        matches = url.match(new RegExp(youTubeLink)),
        id = matches ? (matches[1] || matches[2]) : null;

        return id ? ('//www.youtube.com/embed/' + id) : url;
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
    normalizeIFrame: function (view, newUrl) {
        var
        iframe = view.$('iframe'),
        url = this.normalizeLink(newUrl || iframe.attr('src'));

        if (iframe.attr('src') != url) {
            iframe.attr('src', url);
        }
    }
});
