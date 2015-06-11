define(['q', 'backbone', 'underscore'], function (Q, Backbone, _) {

    'use strict';

    var transformComment = function (comment) {
        var $comment = $(comment);
        return $comment
            .attr('data-id', $comment.attr('id').match(/_(.+)/)[1])
            .attr('id', null);
    };


    return Backbone.Model.extend({
        initialize: function() {
            this.set('$loading', true);
        },

        fetchComments: function() {
            var self = this;

            return Q(jQuery.ajax({
                url: STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/blubber/streams/more_comments',
                data: {
                    thread_id: this.id,
                    cid:       this.cid,
                    count:     'all'
                },
                dataType: 'json',
                type: 'GET'
            })).then(

                // success
                function (response) {
                    var comments = _(response.comments).chain().pluck('content').map(transformComment).value();

                    self.set({
                        '$loading': false,
                        'comments': comments.reverse()
                    });
                },

                // error
                function (error) {
                    self.set('$error', error);
                    console.log(error);
                });
        },

        addComment: function (comment) {
            var comments = _.clone(this.get('comments'));
            comments.push(transformComment(comment));
            this.set('comments', comments);
        }
    });
});
