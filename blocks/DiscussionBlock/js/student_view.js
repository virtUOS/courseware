define(['q', 'underscore', 'autosize', 'assets/js/student_view', 'assets/js/url',
        'assets/js/templates', './thread_model', './threads_collection'],
       function (Q, _, autosize, StudentView, helper, templates, Thread, ThreadsCollection) {

    'use strict';


    return StudentView.extend({
        events: {
            'keydown .writer textarea': function (event) {
                if (event.keyCode === 13 && !event.altKey && !event.ctrlKey && !event.shiftKey) {
                    this.write(event.target);
                    event.preventDefault();
                }
            }
        },


        initialize: function (options) {
            this.threads = new ThreadsCollection();
            this.threads.reset(
                _.map(this.$('article.thread'), function(el) {
                    return new Thread({ id: $(el).attr('id') });
                })
            );

            this.listenTo(this.threads, 'change', this.render);
            this.listenTo(this.threads, 'update', this.render);
        },

        render: function() {
            console.log("rendering DiscussionBlock");

            this.threads.each(function (thread) {
                // clear the comments list
                var ul = this.$('#' + thread.id + ' ul').empty();

                // insert all comments
                ul.append(thread.get('comments'));

                if (!thread.get('$loading')) {
                    this.$('#' + thread.id).removeClass('loading');
                }
            }, this);

            return this;
        },

        postRender: function() {
            this.loadThreads();
            autosize(this.$('.writer textarea'));
        },

        loadThreads: function() {
            var self = this;
            Q.all(this.threads.invoke('fetchComments')).done(function () {
                self.render();
            });
        },

        alreadyWriting: false,

        write: function (textarea) {
            var $textarea = this.$(textarea),
                content = $textarea.val(),
                thread_id = $textarea.closest('.thread').attr('id'),
                self = this;

            if (!content || this.alreadyWriting) {
                return;
            }

            this.alreadyWriting = true;

            Q(jQuery.ajax({
                url: STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/blubber/streams/comment',
                data: {
                    context:      STUDIP.URLHelper.parameters.cid,
                    context_type: 'course',
                    thread:       thread_id,
                    content:      content
                },

                dataType: 'json',

                type: 'POST'
            }))
                .then(
                    // success
                    function (response) {
                        self.alreadyWriting = false;

                        console.log("added comment", response);
                        var thread = self.threads.findWhere({ id: thread_id });
                        thread.addComment(response.content);



                        //insertComment(thread, response.posting_id, response.mkdate, response.content);

                        var insertComment = function (thread_id, posting_id, mkdate, comment) {
                            if (jQuery("#posting_" + thread_id + " ul.comments > li").length === 0) {
                                jQuery(comment).appendTo("#posting_" + thread_id + " ul.comments").hide().fadeIn();
                            } else {
                                var already_inserted = false;
                                jQuery("#posting_" + thread_id + " ul.comments > li").each(function (index, li) {
                                    if (!already_inserted && jQuery(li).attr("mkdate") > mkdate) {
                                        jQuery(comment).insertBefore(li).hide().fadeIn();
                                        already_inserted = true;
                                    }
                                });
                                if (!already_inserted) {
                                    var top = jQuery(document).scrollTop();
                                    jQuery(comment).appendTo("#posting_" + thread_id + " ul.comments").hide().fadeIn();
                                    var comment_top = jQuery("#posting_" + posting_id).offset().top;
                                    var height = jQuery("#posting_" + posting_id).height() +
                                            + 15; //2 * padding + 1 wegen des Border
                                    if (comment_top < top) {
                                        jQuery(document).scrollTop(top + height);
                                    }
                                }
                            }
                        };
                    },

                    // error
                    function (error) {
                        self.alreadyWriting = false;

                        var errorMessage = 'Could not send comment: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
                .done();
        }
    });
});
