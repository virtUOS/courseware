import Backbone from 'backbone'
import _ from 'underscore'
import $ from 'jquery'
import helper from 'js/url'

var transformComment = function (comment) {
  var $comment = $(comment);
  return $comment
    .attr('data-id', $comment.attr('id').match(/_(.+)/)[1])
    .attr('id', null);
};


export default Backbone.Model.extend({
  initialize() {
    this.set('$loading', true);
  },

  fetchComments() {
    var self = this;

    return helper.ajax({
      url: window.STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/blubber/streams/more_comments',
      data: {
        thread_id: this.id,
        cid: this.get('courseid'),
        count: 'all'
      },
      dataType: 'json',
      type: 'GET'
    }).then(function (response) {
      var comments = _(response.comments).chain().pluck('content').map(transformComment).value();

      self.set({
        '$loading': false,
        'comments': comments.reverse()
      });
    }).catch(function (error) {
      self.set('$error', error);
      console.log(error);
    });
  },

  addComment(comment) {
    this.set('comments', [ ...this.get('comments'), transformComment(comment)]);
  }
});
