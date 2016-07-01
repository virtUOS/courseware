define(['./topic', 'backbone', 'underscore'],
function (TopicView, Backbone, _) {

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
