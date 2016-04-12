define(['backbone', 'underscore'], function (Backbone, _) {

    var createDispatcher = function () {
        return _.extend(_.clone(Backbone.Events), {
            selectTopic: function (topic) {
                this.trigger('SELECT_TOPIC', topic);
                this.startCountdown(topic);
            },

            selectAgeGroup: function (ageGroup) {
                this.trigger('SELECT_AGE_GROUP', ageGroup);
                this.startCountdown(ageGroup);
            },

            startEditing: function () {
                this.trigger('START_EDITING');
            },

            stopEditing: function () {
                this.trigger('STOP_EDITING');
            },

            editTopicRequest: function (topic, content) {
                this.trigger('EDIT_TOPIC_REQUEST', topic, content);
            },

            editTopicSuccess: function (topic) {
                this.trigger('EDIT_TOPIC_SUCCESS', topic);
            },

            editTopicFailure: function (topic, error) {
                this.trigger('EDIT_TOPIC_FAILURE', topic, error);
            },

            completeTopicRequest: function (topic) {
                this.trigger('COMPLETE_TOPIC_REQUEST', topic);
            },

            startCountdown: function (topic) {
                this.trigger('START_TOPIC_COUNTDOWN', topic);
            },

            completeAgeGroup: function (topic) {
                this.trigger('COMPLETE_AGE_GROUP', topic);
            },

            saveAgeGroupsTest: function (ageGroupsTests) {
                this.trigger('SAVE_AGE_GROUPS_TESTS', ageGroupsTests);
            },

            receiveAgeGroupsTest: function (data) {
                this.trigger('RECEIVE_AGE_GROUPS_TESTS', data);
            },

            failAgeGroupsTest: function (response) {
                this.trigger('FAIL_AGE_GROUPS_TESTS', response);
            },

            selectSelfTest: function (ageGroup) {
                this.trigger('SELECT_SELF_TEST', ageGroup);
            },

            passSelfTest: function () {
                this.trigger('PASS_SELF_TEST');
            }
        });
    };

    return createDispatcher;
});
