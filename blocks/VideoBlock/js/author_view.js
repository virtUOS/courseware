define(['assets/js/author_view', 'assets/js/url'], function (
    AuthorView, helper
) {
    'use strict';

    function normalizeYouTubeLink(url) {
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
    }
    function normalizeMatterhornLink(url) {
        return url;
    }
    function normalizeLink(url) {
        return normalizeMatterhornLink(normalizeYouTubeLink(url));
    }

    return AuthorView.extend({
        events: {
            'click button': function (event) {
                var view = this;

                helper
                .callHandler(view.model.id, 'save', {
                    url: normalizeLink(view.$('input').val())
                })
                .then(function () { // success
                    $(event.target).addClass('accept');
                    view.switchBack();
                }, function () {    // error
                    alert('Fehler!');
                    console.log('fail', arguments);
                });
            }
        },
        initialize: function (options) {
            // console.log('initialize VideoBlock author view', this, options);
        },
        render: function() { return this; }
    });
});
