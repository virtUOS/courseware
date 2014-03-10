define(['assets/js/student_view'], function (StudentView) {
    'use strict';
    return StudentView.extend({
        events: {},
        initialize: function(options) {
            // console.log('initialize VideoBlock student view', this, options);
        },
        render: function() { return this; }
    });
});
