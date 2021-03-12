import jQuery from 'jquery'
import _ from 'underscore'
import autosize from 'autosize'
import helper from 'js/url'
import StudentView from 'js/student_view'
import Thread from './thread_model'
import ThreadsCollection from './threads_collection'

export default StudentView.extend({
    events: {
        'keydown .comment-writer textarea': function (event) {
            if (event.keyCode === 13 && !event.altKey && !event.ctrlKey && !event.shiftKey) {
                this.write(event.target);
                event.preventDefault();
            }
        },

        'click article.thread a h1': 'expandOrCollapseThread'
    },

    initialize() {
        this.threads = new ThreadsCollection();

        this.listenTo(this.threads, 'change', this.render);
        this.listenTo(this.threads, 'update', this.render);
    },

    initializeFromDOM() {
        this.threads.reset(
            _.map(this.$('article.thread'), function (el) {
                var id = jQuery(el).attr('id'),
                    courseid = jQuery(el).attr('data-courseid');

                // TODO: this should probably go to ThreadModel
                if (!id || id === '' || !courseid || courseid === '') {
                    throw new Error('Could not initialize DiscussionBlock from DOM');
                }

                return new Thread({id: id, courseid: courseid});
            })
        );
    },

    render() {
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

    postRender() {
        this.loadThreads();
        autosize(this.$('.comment-writer textarea'));
    },

    loadThreads() {
        var self = this;
        Promise.all(this.threads.invoke('fetchComments')).then(function () {
            self.render();
        });
    },

    alreadyWriting: false,

    // TODO: put this into ThreadModel
    write(textarea) {
        var $textarea = this.$(textarea),
            comment = $textarea.val(),
            $thread_el = $textarea.closest('.thread'),
            thread_id = $thread_el.attr('id'),
            self = this;

        if (!comment || this.alreadyWriting) {
            return;
        }

        this.alreadyWriting = true;
        $textarea.val('');


        $.ajax({
            url: STUDIP.ABSOLUTE_URI_STUDIP + `jsonapi.php/v1/blubber-threads/${thread_id}/comments`,
            type: 'POST',
            headers: {
                'Content-Type': 'application/vnd.api+json'
            },
            data: JSON.stringify({
                data: {
                    attributes: {
                        content: comment,
                    }
                }
            }),
            success: comment => {
                var content = "<li>";
                self.alreadyWriting = false;
                $.ajax({
                    url: STUDIP.ABSOLUTE_URI_STUDIP + `jsonapi.php/v1/users/${comment.data.relationships.author.data.id}`,
                    type: 'GET',
                    success: user => {
                        content += "<p><strong>" + user.data.attributes['formatted-name'] + "</strong></p>";
                        content += comment.data.attributes['content-html'];
                        content += "</li>";
                        var thread = self.threads.findWhere({id: thread_id});
                        thread.addComment(content);
                    },
                    error: error => {
                        console.log(error);
                    }
                });
            },
            error: error => {
                self.alreadyWriting = false;
                $textarea.val(comment);
        
                console.log(error);
                alert('Could not send comment');
            }
        });

    },

    expandOrCollapseThread(event) {
        event.preventDefault();
        var $thread = this.$(event.target).closest('article');
        $thread.toggleClass('open');
    }
});
