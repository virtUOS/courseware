define(['backbone', 'underscore', 'assets/js/templates', 'assets/js/block_model', 'assets/js/block_types'], function (Backbone, _, templates, BlockModel, block_types) {

    var decorateAgeGroup = function (group) {
        var selfTest = group.get('selfTest'),
            test_id = selfTest && selfTest.test_id,
            selectedSelfTest = group.collection.selectedSelfTest;

        return _.extend(
            {
                testSelected: function () { return test_id === this.id ? 'selected' : ''; },
                blockSelected: function () { return selectedSelfTest === group.id ? 'selected' : ''; },
                hasSelfTest: function () { return test_id !== null && test_id !== ''; }
            },
            group.attributes
        );
    };

    var SelfTestsView = Backbone.View.extend({
        tagName: 'article',

        className: 'self-tests',

        events: {
            'submit .mode-author form.ageGroupsTests': 'onSubmitAgeGroupsTests',
            'submit .mode-student form.selectSelfTest': 'onSelectSelfTest',
            'change .mode-author select': 'onChangeSelectSelfTest'
        },

        initialize: function (options) {
            this.tests = options.tests;
            this.topics = options.topics;
            this.dispatcher = options.dispatcher;

            this.listenTo(this.topics, 'change', this.render);
            this.listenTo(this.dispatcher, 'RECEIVE_AGE_GROUPS_TESTS', this.onReceiveAgeGroupsTests);
            this.listenTo(this.dispatcher, 'FAIL_AGE_GROUPS_TESTS', this.onFailAgeGroupsTests);
        },

        render: function () {

            var data = {
                ageGroups: _.map( this.topics.ageGroups, decorateAgeGroup),
                hasCompletedAgeGroups: _.any(this.topics.ageGroups, function (group) { return group.get('$thoroughlyComplete'); }),
                tests: _.pluck(this.tests.models, 'attributes'),
                testBlock: {
                    id: this.topics.selectedSelfTest,
                    ageGroup: this.topics.get(this.topics.selectedSelfTest),
                    test: this.tests && this.tests.findWhere('id', this.topics.selectedSelfTest).attributes
                },
                selectedSelfTest: this.topics.selectSelfTest,
                formMode: this.formMode
            };
            this.$el.html(templates('WallNewspaperBlock', 'self_tests_view', data));

            return this;
        },

        onChangeSelectSelfTest: function () {
            this.formMode = '';
            // TODO: blöder hack
            this.$('form.ageGroupsTests').removeClass('success fail');
        },

        onReceiveAgeGroupsTests: function () {
            this.formMode = 'success';
            this.render();
        },

        onFailAgeGroupsTests: function () {
            this.formMode = 'fail';
            this.render();
        },

        onSubmitAgeGroupsTests: function (event) {
            event.preventDefault();

            this.formMode = 'loading';

            var ageGroupsTest = _.reduce(
                this.$('.mode-author form.ageGroupsTests select'),
                function (memo, select) {
                    var testId = select.options[select.selectedIndex].value;
                    memo[select.getAttribute('data-age-group-id')] = testId === '' ? null : '' + testId;
                    return memo;
                },
                {}
            );

            this.dispatcher.saveAgeGroupsTest(ageGroupsTest);
        },

        onSelectSelfTest: function (event) {
            event.preventDefault();
            var ageGroup = this.$('.mode-student form.selectSelfTest select').val();
            this.dispatcher.selectSelfTest(ageGroup === '' ? null : '' + ageGroup);
        },
    });

    return SelfTestsView;
});
