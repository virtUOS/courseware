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

        onSelect: function (event) {
            if (!Backbone.$(event.target).hasClass('has-subtopics')) {
                this.dispatcher.selectTopic(this.model);
            }
        }
    });

    var AgeGroupTopicsView = Backbone.View.extend({
        tagName: 'nav',

        className: 'age-group-topics',

        initialize: function (options) {
            this.dispatcher = options.dispatcher;
            this.listenTo(this.model, 'change', this.render);
        },

        _topicViews: [],

        render: function () {
            _.invoke(this._topicViews, 'remove');
            this._topicViews = this.model.children().map(function (topic) { return new TopicView({ model: topic, dispatcher: this.dispatcher }); }, this);
            this.$el.append(this._topicViews.map(function (view) { return view.render().$el; }));

            return this;
        },

        remove: function() {
            _.invoke(this._topicViews, 'remove');
            return Backbone.View.prototype.remove.call(this);
        }
    });

    return AgeGroupTopicsView;
});
