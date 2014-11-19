define(['assets/js/student_view', 'utils'], function (StudentView, Utils) {
    'use strict';
    return StudentView.extend({
        events: {},
        initialize: function(options) { },
        render: function() { return this; },
        postRender: function () {
            //Utils.normalizeIFrame(this);
        }
    });
});
