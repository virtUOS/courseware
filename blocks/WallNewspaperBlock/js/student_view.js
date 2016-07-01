define([ './views/age_group_select', './views/age_group_topics', './views/content', './views/self_test_block', './views/self_tests',
'./models/topic', './models/topic_collection', './create_dispatcher', 'backbone', 'underscore', 'assets/js/student_view'],
function (AgeGroupSelectView, AgeGroupTopicsView, ContentView, SelfTestBlockView, SelfTestsView,
Topic, TopicCollection, createDispatcher, Backbone, _, StudentView) {

    var parseJSON = function (block, text) {
        var rawJSON = JSON.parse(text);
        return {
            collection: transformTree(block, rawJSON.tree),
            tests: transformTests(block, rawJSON.tests)
        };
    };

    var transformTree = function (block, tree) {
        var collection = tree.reduce(makeTopics, new TopicCollection({ dispatcher: block.dispatcher }));
        collection.ageGroups = tree.map(function (ageGroup) { return collection.get(ageGroup.id); });
        collection.block = block.model;
        return collection;
    }

    var transformTests = function (block, testsArray) {
        var tests = new Backbone.Collection(testsArray);
        return tests;
    };

    var makeTopics = function (collection, json) {
        var topic = new Topic(_.extend({}, json, { childTopics: _.pluck(json.childTopics, 'id') }), { collection: collection });
        collection.add(topic);
        return json.childTopics.reduce(makeTopics, collection);
    };


    return StudentView.extend({
        // complete model by retrieving the attributes from the
        // DOM instead of making a roundtrip to the server
        initializeFromDOM: function () {
            this.dispatcher = createDispatcher();

            var json = parseJSON(this, this.$('script')[0].textContent);

            // topic collection
            this.collection = json.collection;
            // this.dispatcher.selectAgeGroup(_.head(this.collection.ageGroups));

            // test collection
            this.tests = json.tests;

            this.listenTo(this.collection, 'change:selectedTopic',    this.renderSelectedTopic);
            this.listenTo(this.collection, 'change:selectedAgeGroup', this.renderSelectedAgeGroup);

            this.render();
        },

        remove: function () {
            var childViews = ['_contentView', '_ageGroupTopicView', '_selfTestsView', '_selfTestBlockView'];
            _.each(childViews, function (childView) { if (this[childView]) { this[childView].remove(); } }, this);
            return Backbone.View.prototype.remove.call(this);
        },

        render: function () {
            var $nav = this.$('nav.age-group-selects');

            $nav.empty() // FIXME: Die entfernten Elemente müssen aufgeräumt/benachrichtigt werden ...
                .append(
                    this.collection.ageGroups.map(function (ageGroup) {
                        return (new AgeGroupSelectView({ model: ageGroup, dispatcher: this.dispatcher })).render().el;
                    }, this));

            this.renderSelectedAgeGroup();
            this.renderSelectedTopic();
            this.renderSelfTests();

            return this;
        },

        _contentView: null,

        renderSelectedTopic: function () {
            var old_view = this._contentView, new_content;

            if (this.collection.selectedTopic) {
                this._contentView = new ContentView({ dispatcher: this.dispatcher, model: this.collection.selectedTopic });
                new_content = this._contentView.render().$el;

                if (old_view) {
                    old_view.$el.replaceWith(new_content);
                    old_view.remove();
                } else {
                    this.$('> article.content').replaceWith(new_content);
                }
            }
        },

        _ageGroupTopicView: null,

        renderSelectedAgeGroup: function () {
            var old_view = this._ageGroupTopicView, new_content = null;

            if (this.collection.selectedAgeGroup) {
                this._ageGroupTopicView = new AgeGroupTopicsView({ dispatcher: this.dispatcher, model: this.collection.selectedAgeGroup });
                new_content = this._ageGroupTopicView.render().$el;

                if (old_view) {
                    old_view.$el.replaceWith(new_content);
                    old_view.remove();
                } else {
                    this.$('nav.age-group-topics').replaceWith(new_content);
                }
            }
        },

        _selfTestsView: null,
        _selfTestBlockView : null,

        renderSelfTests: function () {
            this._selfTestsView = new SelfTestsView({ dispatcher: this.dispatcher, topics: this.collection, tests: this.tests });
            this.$('article.self-tests').replaceWith(this._selfTestsView.render().$el);

            this._selfTestBlockView = new SelfTestBlockView({ dispatcher: this.dispatcher, topics: this.collection });
            this.$('article.self-test-block').replaceWith(this._selfTestBlockView.render().$el);
        }
    });
});
