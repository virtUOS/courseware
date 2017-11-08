define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack",
            "change .download-folder": "selectFolder"
        },

        initialize: function(options) {
            Backbone.on('beforemodeswitch', this.onModeSwitch, this);
            Backbone.on('beforenavigate', this.onNavigate, this);
        },

        render: function() {
            return this;
        },

        postRender: function() {
            var $view = this;
            var $folders = $view.$el.find(".download-folder option");
            var $stored_folder = $view.$el.find(".download-stored-folder").val();
            $folders.each(function(){
                    if($(this).attr("folder_id") == $stored_folder) {
                            $(this).prop("selected", true);
                    }
            });

            var $files = $view.$el.find(".download-file option");
            var $stored_file = $view.$el.find(".download-stored-file").val();
            $files.each(function(){
                    if($(this).attr("file_id") == $stored_file) {
                            $(this).prop("selected", true);
                    }
            });
            
        },

        onNavigate: function(event){
            if(!$("section .block-content button[name=save]").length) {
                return;
            }
            if(event.isUserInputHandled) {
                return;
            }
            event.isUserInputHandled = true;
            Backbone.trigger('preventnavigateto', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie die Seite trotzdem verlassen?'));
        },

        onModeSwitch: function (toView, event) {
            if (toView != 'student') {
                return;
            }
            // the user already switched back (i.e. the is not visible)
            if (!this.$el.is(':visible')) {
                return;
            }
            // another listener already handled the user's feedback
            if (event.isUserInputHandled) {
                return;
            }
            event.isUserInputHandled = true;
            Backbone.trigger('preventviewswitch', !confirm('Es gibt nicht gespeicherte Änderungen. Möchten Sie trotzdem fortfahren?'));
        }, 

        onSave: function (event) {
            var view = this;
            var $file = this.$el.find(".download-file");
            var $folder = this.$el.find(".download-folder");
            var $file_val = $file.val();
            var $file_id = $file.find('option:selected').attr("file_id");
            var $file_name = $file.find('option:selected').attr("file_name");
            var $folder_id = $folder.find('option:selected').attr("folder_id");
            var $download_title = this.$("input[name='download-title']").val();
            var $download_info = this.$("input[name='download-info']").val();
            var $download_success = this.$("input[name='download-success']").val();

            helper
                .callHandler(this.model.id, "save", {
                    file: $file_val, 
                    file_id: $file_id, 
                    file_name: $file_name, 
                    folder_id: $folder_id, 
                    download_title: $download_title, 
                    download_info: $download_info, 
                    download_success: $download_success
                })
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
        },

        selectFolder: function() {
            var view = this;
            var $folder = this.$el.find(".download-folder").find('option:selected').val();
            helper
                .callHandler(this.model.id, "setfolder", {folder: $folder})
                .then(
                    // success
                    function (event) {
                        jQuery(event.target).addClass("accept");
                        if(event) {
                            view.showFiles(event);
                        }
                    },

                    // error
                    function (error) {
                        var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    })
            .done();
        },

        showFiles: function($allfiles) {
            var $files = this.$el.find(".download-file");
            $files.find('option').remove();
            $.each($allfiles, function(key, value){
               $files.append($('<option>', {
                    value: value.name,
                    text: value.filename,
                    file_id: value.dokument_id,
                    file_name: value.filename
                }));
            });
        }
        
    });
});
