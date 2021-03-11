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

        STUDIP.jsonapi.POST(`blubber-threads/${thread_id}/comments`, {
            data: {
                data: {
                    attributes: {
                        content: comment,
                    }
                }
            }
        }).done((comment) => {
            var content = "<li>";
            self.alreadyWriting = false;
            STUDIP.jsonapi.GET(`users/${comment.data.relationships.author.data.id}`).done((user) => {
                content += "<p><strong>" + user.data.attributes['formatted-name'] + "</strong></p>";
                content += comment.data.attributes['content-html'];
                content += "</li>";
                var thread = self.threads.findWhere({id: thread_id});
                thread.addComment(content);
            }).catch(function (error) {
                console.log(error);
            });

        }).catch(function (error) {
            self.alreadyWriting = false;
            $textarea.val(comment);

            console.log(error)
            debugger

            var errorMessage = [
                'Could not send comment:',
                jQuery.parseJSON(error.responseText).reason
            ].join('');
            alert(errorMessage);
            console.log(errorMessage, arguments);
        })
    },

    expandOrCollapseThread(event) {
        event.preventDefault();
        var $thread = this.$(event.target).closest('article');
        $thread.toggleClass('open');
    }
});