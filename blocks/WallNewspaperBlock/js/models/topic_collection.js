define(['./topic', 'backbone', 'underscore', 'assets/js/url', 'utils', 'q'],
function (Topic, Backbone, _, helper, Utils, Q) {

    var VideoUtils = {
        getURL: function (videourl, videotype) {
            var url, message;

            switch (videotype) {

            case 'matterhorn':
                if (videourl.indexOf('?id=') === -1 ) {
                    message = 'Keine Matterhorn ID übergeben. Wert wurde zurückgesetzt.';
                } else {
                    url = videourl.replace('/engage/ui/watch.html?', '/engage/ui/embed.html?').split('&')[0];
                }
                break;

            case 'dfb':
                url = '//tv.dfb.de/player_frame.php?view=' + videourl;
                break;


            case 'url':
                if (!videourl.match(/youtube\.(com|de)/)) {
                    url = videourl;
                    break;
                }

                // direkt weiter mit der YouTube-Mechanik

            case 'youtube':
                var youtubeid = Utils.getYouTubeId(videourl);
                if (!youtubeid) {
                    message = 'Fehlerhafte YouTube-ID.';
                } else {
                    url = '//www.youtube.com/embed/' + youtubeid;
                }
                break;
            }

            return { url: url, message: message };
        }
    };

    var TopicCollection = Backbone.Collection.extend({
        model: Topic,

        initialize: function (options) {
            this.dispatcher = options.dispatcher;

            this.listenTo(this.dispatcher, 'SELECT_TOPIC', this.onSelectTopic);

            this.listenTo(this.dispatcher, 'SELECT_AGE_GROUP', this.onSelectAgeGroup);
            this.listenTo(this.dispatcher, 'EDIT_TOPIC_REQUEST', this.onEditTopic);

            this.listenTo(this.dispatcher, 'EDIT_TOPIC_SUCCESS', this.onEditTopicSuccess);
            this.listenTo(this.dispatcher, 'EDIT_TOPIC_FAILURE', console.log.bind(console, 'error'));

            this.listenTo(this.dispatcher, 'COMPLETE_TOPIC_REQUEST', this.onCompleteTopic);

            this.listenTo(this.dispatcher, 'START_TOPIC_COUNTDOWN', this.onStartTopicCountdown);

            this.listenTo(this.dispatcher, 'SAVE_AGE_GROUPS_TESTS', this.onSaveAgeGroupsTests);
            this.listenTo(this.dispatcher, 'SELECT_SELF_TEST', this.onSelectSelfTest);

            this.listenTo(this.dispatcher, 'PASS_SELF_TEST', this.onPassSelfTest);

            this.listenTo(this, 'change', this.onCompleteAgeGroup);
        },

        onSelectTopic: function (new_topic) {
            var old_topic = this.selectedTopic;
            this.selectedTopic = new_topic;

            if (old_topic !== new_topic) {
                old_topic && old_topic.set('$selected', false);
                new_topic && new_topic.set('$selected', true);
            }
        },

        onSelectAgeGroup: function (ageGroup) {
            this.selectedAgeGroup = ageGroup;
            this.onSelectTopic(ageGroup);
        },

        onEditTopic: function (topic, content) {

            // validate text and video
            var cleanedUp = VideoUtils.getURL(content.video_url, content.video_type);
            // TODO cleanedUp.message könnte Statusmeldungen beinhalten. Was machen wir damit?

            topic.set({ $loading: true, video: cleanedUp.url, text: content.text });

            topic.callHandler('save', { video: topic.get('video'), text: topic.get('text') })
                .then(
                    _.bind(this.dispatcher.editTopicSuccess, this.dispatcher, topic),
                    _.bind(this.dispatcher.editTopicFailure, this.dispatcher, topic)
                )
                .done();
        },

        onEditTopicSuccess: function (topic) {
            topic.set('$loading', false);
        },

        onEditTopicError: function (topic, error) {
            topic.set('$loading', false);
        },

        onCompleteTopic: function (topic) {
            topic.set('complete', true);
            topic.callHandler('complete', { topic_id: topic.id }).done();
        },

        _countdown: false,

        onStartTopicCountdown: function (topic) {

            var timeout_10_seconds = 10 * 1000;

            if (this._countdown) {
                clearTimeout(this._countdown);
                this._countdown = false;
            }

            if (!topic.get('complete')) {
                this._countdown = setTimeout((function () {
                    this.dispatcher.completeTopicRequest(topic);
                    this._countdown = false;
                }).bind(this), timeout_10_seconds);
            }
        },

        onCompleteAgeGroup: function (topic) {
            if (_.contains(this.ageGroups, topic) && topic.isThoroughlyComplete()) {
                this.dispatcher.completeAgeGroup(topic);
            }
        },

        onSaveAgeGroupsTests: function (age_groups_tests) {
            _.each(age_groups_tests, function (test_id, age_group_id) {
                var ageGroup = _.find(this.ageGroups, function (group) { return group.id === age_group_id });
                if (ageGroup) {
                    ageGroup.set({ selfTest: _.extend({}, ageGroup.get('selfTest'), { test_id: test_id }) });
                    if (test_id === null && ageGroup.id === this.selectedSelfTest) {
                        this.selectedSelfTest = null;
                    }
                }
            }, this);

            var dispatcher = this.dispatcher;

            Q(helper.callHandler(this.block.id, 'save_age_groups_tests',
                                 { age_groups_tests: age_groups_tests }))
                .then(
                    function (data) {
                        console.log("receiveAgeGroupsTest");
                        dispatcher.receiveAgeGroupsTest(data);
                    },
                    function (response) {
                        console.log("failAgeGroupsTest");
                        dispatcher.failAgeGroupsTest(response);
                    })
                .done();
        },

        onSelectSelfTest: function (ageGroup) {
            this.selectedSelfTest = ageGroup;
        },

        onPassSelfTest: function () {
            helper.callHandler(this.block.id, 'pass_self_test', {});
            Backbone.trigger('WallNewspaperBlock:passed', parseInt(this.block.id, 10));
        }
    });

    var triggeringProperty = function (name) {
        var privatePropertyName = '_' + name;

        return {
            get: function () { return this[privatePropertyName]; },

            set: function (new_value) {
                var old_value = this[privatePropertyName];
                if (old_value !== new_value) {
                    this[privatePropertyName] = new_value;
                    this.trigger('change:' + name, new_value, old_value);
                }
            }
        };
    };

    Object.defineProperties(TopicCollection.prototype, {
        selectedTopic: triggeringProperty('selectedTopic'),
        selectedAgeGroup: triggeringProperty('selectedAgeGroup'),
        selectedSelfTest: triggeringProperty('selectedSelfTest')
    });

    return TopicCollection;
});
