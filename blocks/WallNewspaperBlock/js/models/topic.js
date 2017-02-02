define(['backbone', 'underscore', 'assets/js/url'],
function (Backbone, _, helper) {

    var Topic = Backbone.Model.extend({
        initialize: function() {
            this.listenTo(this.collection, 'change', this.onTopicCompleted);
        },

        onTopicCompleted: function (topic) {
            if (_.contains(this.get('childTopics'), topic.id)) {
                if (this.isThoroughlyComplete()) {
                    this.trigger('change', this);
                }
            }
        },

        children: function () {
            if (!this.has('$children')) {
                var childIDs = this.get('childTopics');
                this.set('$children', this.collection.filter(function (topic) { return _.contains(childIDs, topic.id); }), { silent: true });
            }
            return this.get('$children');
        },

        isThoroughlyComplete: function () {
            var result = this.get('$thoroughlyComplete') || (this.get('complete') && _.every(this.children(), function (child) { return child.isThoroughlyComplete(); }));
            if (result) {
                this.set({ $thoroughlyComplete: true }, { silent: true });
            }
            return result;
        },

        callHandler: function (handler, content) {
            var payload = _.extend({}, content, { topic_id: this.id });
            return helper.callHandler(this.collection.block.id, handler, payload);
        }
    });

    return Topic;
});
