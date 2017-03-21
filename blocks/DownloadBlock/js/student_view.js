define(['assets/js/student_view', 'assets/js/url', 'assets/js/templates'], function (StudentView, helper, templates) {
    
    'use strict';
    
    return StudentView.extend({
        events: {
            "click button[name=download]":   "onDownload",
        },
        
        initialize: function(options) {
        },

        render: function() {
            return this; 
        },
        
        postRender: function() {
        },
        
        onDownload: function() {
            this.model.set('confirmed', true);
            this.model.set('file', this.$("input[name='file']").val());
            this.model.set('file_name', this.$("input[name='file_name']").val());
            this.model.set('file_id', this.$("input[name='file_id']").val());
            this.model.set('download_title', this.$("input[name='download_title']").val());
            this.model.set('download_info', this.$("input[name='download_info']").val());
            this.model.set('download_success', this.$("input[name='download_success']").val());
            
            this.$el.html(templates("DownloadBlock", 'student_view', _.clone(this.model.attributes)));
            helper
                .callHandler(this.model.id, 'download', {})
                .then(
                    // success
                    function () {
                        
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
                .done();
        }
    });
});


