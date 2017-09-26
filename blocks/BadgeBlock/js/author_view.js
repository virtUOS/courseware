define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack",
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        postRender: function() {

            
        },

        onSave: function (event) {
            var view = this;
            var $file = this.$el.find(".download-file");
            var $file_val = $file.val();
            var $file_id = $file.find('option:selected').attr("file_id");
            var $file_name = $file.find('option:selected').attr("file_name");
            var $file_height = this.$("input[name='file_height']").val();
            var $file_width = this.$("input[name='file_width']").val();

            var $download_title = this.$("input[name='download-title']").val();
            var $download_info = this.$("input[name='download-info']").val();
            var $download_success = this.$("input[name='download-success']").val();

            //textarea.remove();
            helper
                .callHandler(this.model.id, "save", {file: $file_val, file_id: $file_id, file_name: $file_name, file_height: $file_height, file_width: $file_width, download_title: $download_title, download_info: $download_info, download_success: $download_success})
                .then(
                    // success
                    function () {
                        jQuery(event.target).addClass("accept");
                        view.switchBack();
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
