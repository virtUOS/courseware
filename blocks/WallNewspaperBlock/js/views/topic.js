define(['backbone', 'underscore', 'assets/js/templates'],

function (Backbone, _, templates) {

    var TopicView = Backbone.View.extend({
        tagName: 'div',

        className: 'topic',

        events: {
            'click > .title': 'onSelect'
        },

        initialize: function (options) {
            this.dispatcher = options.dispatcher;

            this.listenTo(this.model, 'change', this.render);
        },

        _childTopicViews: [],

        render: function () {
            _.invoke(this._childTopicViews, 'remove');
            this._childTopicViews = this.model.children().map(function (topic) { return (new TopicView({ model: topic, dispatcher: this.dispatcher })).render(); }, this);

            var data = _.extend({}, this.model.attributes, {
                hasSubtopics: this.model.children().length > 0,
                $thoroughlyComplete: this.model.isThoroughlyComplete()
            });
            this.$el.html(templates('WallNewspaperBlock', 'topic_view', data));

            if (data.hasSubtopics) {
                this.$('> nav').append(_.pluck(this._childTopicViews, '$el'));
            }

            return this;
        },

        remove: function() {
            _.invoke(this._childTopicViews, 'remove');
            return Backbone.View.prototype.remove.call(this);
        },

        onSelect: function () {
            this.dispatcher.selectTopic(this.model);
        }
    });

    return TopicView;
});
