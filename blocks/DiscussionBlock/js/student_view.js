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

        return new Thread({ id: id, courseid: courseid });
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

    // TODO: clean this up; put event handling into
    //       this.events etc.
    var textarea = this.$('.comment-writer textarea');
    textarea.on('drop', function (event) {
      event.preventDefault();
      var files = 0;
      var file_info = event.originalEvent.dataTransfer.files || {};
      var data = new FormData();
      var context_id = textarea.closest('.thread').attr('data-courseid');
      var context_type = 'course';

      jQuery.each(file_info, function (index, file) {
        if (file.size > 0) {
          data.append(index, file);
          files += 1;
        }
      });
      if (files > 0) {
        jQuery(textarea).addClass('uploading');
        jQuery.ajax({
          'url': window.STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/blubber/streams'
            + '/post_files?context=' + context_id
            + '&context_type=' + context_type
            + (context_type === 'course' ? '&cid=' + context_id : ''),
          'data': data,
          'cache': false,
          'contentType': false,
          'processData': false,
          'type': 'POST',
          xhr() {
            var xhr = jQuery.ajaxSettings.xhr();
            //workaround for FF<4 https://github.com/francois2metz/html5-formdata
            if (data.fake) {
              xhr.setRequestHeader('Content-Type', 'multipart/form-data; boundary=' + data.boundary);
              xhr.send = xhr.sendAsBinary;
            }
            return xhr;
          },
          success(json) {
            if (typeof json.inserts === 'object') {
              jQuery.each(json.inserts, function (index, text) {
                jQuery(textarea).val(jQuery(textarea).val() + ' ' + text);
              });
            }
            if (typeof json.errors === 'object') {
              alert(json.errors.join('\n'));
            } else if (typeof json.inserts !== 'object') {
              alert('Fehler beim Dateiupload.');
            }
            jQuery(textarea).trigger('keydown');
          },
          complete() {
            jQuery(textarea).removeClass('hovered').removeClass('uploading');
          }
        });
      }
    });
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
        content = $textarea.val(),
        $thread_el = $textarea.closest('.thread'),
        thread_id = $thread_el.attr('id'),
        courseid = $thread_el.attr('data-courseid'),
        self = this;

    if (!content || this.alreadyWriting) {
      return;
    }

    this.alreadyWriting = true;
    $textarea.val('');

    helper.ajax({
      url: window.STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/blubber/streams/comment',
      data: {
        context:      courseid,
        context_type: 'course',
        thread:       thread_id,
        content:      content
      },

      dataType: 'json',

      type: 'POST'
    }).then(function (response) {
      self.alreadyWriting = false;

      var thread = self.threads.findWhere({ id: thread_id });
      thread.addComment(response.content);

    }).catch(function (error) {
      self.alreadyWriting = false;
      $textarea.val(content);

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
