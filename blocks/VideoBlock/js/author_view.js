define(['assets/js/author_view', 'assets/js/url'], function (
    AuthorView, helper
) {
    'use strict';
    return AuthorView.extend({
        events: {
            'click button': function (event) {
                var view = this,
                    url = view.$('input').val();
                // http://www.youtube.com/watch?v=lvB2nRGMl2c
                var youtube = url.match(/^\s*(?:https?:)?\/\/(?:www\.)?youtube\.com\/watch\?v=(\w*)/);
                if (youtube) {
                    url = '//www.youtube.com/embed/' + youtube[1];
                }

                helper
                .callHandler(view.model.id, 'foo', { url: url })
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
