define(['assets/js/student_view', 'assets/js/url'], function (StudentView, helper) {
    'use strict';
    return StudentView.extend({
        events: {},
        initialize: function(options) { },
        render: function() { return this; },
        postRender: function() {
            var opencastVideoId = this.$el.find('#opencast-video-id').val();
            var that = this;
            if(opencastVideoId) {
                helper.callHandler(this.model.id, 'getOpencastURL', {opencastVideo: opencastVideoId}).then(
                    function (data) {
                        var url = 'http://'+data['url'];
                        that.$('iframe').attr('src', url);
                    }
                )
            }
        }
    });
});
