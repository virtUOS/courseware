import Backbone from 'backbone'
import $ from 'jquery'

export default Backbone.Model.extend({
    initialize() {
        this.set('$loading', true);
    },

    fetchComments() {
        var self = this;
        let content = "";
        STUDIP.jsonapi.GET(`blubber-threads/${this.id}/comments `).done((thread) => {
            if (thread) {
                $.each(thread.data, function (i, comment) {
                    STUDIP.jsonapi.GET(`users/${comment.relationships.author.data.id}`).done((user) => {
                        content += "<li>";
                        content += "<p><strong>" + user.data.attributes['formatted-name'] + "</strong></p>";
                        content += comment.attributes['content-html'];
                        content += "</li>";
                        self.set({
                            '$loading': false,
                            'comments': content
                        });
                    }).catch(function (error) {
                        console.log(error);
                    });
                })
            }
        }).catch(function (error) {
            console.log(error);
        });
    },

    addComment(comment) {
        this.set('comments', [...this.get('comments'), comment]);
    }
});