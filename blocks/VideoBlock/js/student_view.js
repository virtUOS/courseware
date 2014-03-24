define(['assets/js/student_view', './utils'], function (StudentView, Utils) {
    'use strict';
    return StudentView.extend({
        events: {},
        initialize: function(options) {
            Utils.normalizeIFrame(this);
        },
        render: function() { return this; }
    });
});
