import Backbone from 'backbone'
import $ from 'jquery'

export default Backbone.Model.extend({
    initialize() {
        this.set('$loading', true);
    },

    fetchComments() {
        var self = this;
        let content = "";
        $.ajax({
            url: STUDIP.ABSOLUTE_URI_STUDIP + `jsonapi.php/v1/blubber-threads/${this.id}/comments`,
            type: 'GET',
            success: thread => {
                if (thread.data.length) {
                    $.each(thread.data, function (i, comment) {
                        $.ajax({
                            url: STUDIP.ABSOLUTE_URI_STUDIP + `jsonapi.php/v1/users/${comment.relationships.author.data.id}`,
                            type: 'GET',
                            success: user => {
                                content += "<li>";
                                content += "<p><strong>" + user.data.attributes['formatted-name'] + "</strong></p>";
                                content += comment.attributes['content-html'];
                                content += "</li>";
                                self.set({
                                    '$loading': false,
                                    'comments': content
                                });
                            },
                            error: error => {
                                console.log(error);
                            }
                        });
                    })
                } else {
                    self.set({
                        '$loading': false,
                        'comments': content
                    });
                }
            },
            error: error => {
                console.log(error);
            }
        });
    },

    addComment(comment) {
        this.set('comments', [...this.get('comments'), comment]);
    }
});
