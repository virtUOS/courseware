define(['assets/js/author_view', 'assets/js/url'], function (AuthorView, helper) {
    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack",
            "change select[name=test_id]": "setMaxNum"
        },

        initialize: function(options) {
            Backbone.on('beforemodeswitch', this.onModeSwitch, this);
            Backbone.on('beforenavigate', this.onNavigate, this);
        },

        render: function() {
            return this;
        },
        
        postRender: function() {
            var $input = this.$("input[name='test_questions']");
            var $selection = this.$('select[name="test_id"] option:selected');
            if(typeof $selection.attr('select-data') != "undefined") {
                var options = $.parseJSON($selection.attr('select-data'));
                var $count = parseInt(options.count);
                $input.attr('max', $count);
            }
        }, 
        
        setMaxNum: function() {
            var $input = this.$("input[name='test_questions']");
            var $selection = this.$('select[name="test_id"] option:selected');
            
            var options = $.parseJSON($selection.attr('select-data'));
            var $count = parseInt(options.count);
            $input.val($count);
            $input.attr('max', $count);
        
            
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

        onSave: function () {
            var view = this;
            var $test_id = this.$('select[name="test_id"]').val();
            var $test_questions = parseInt(this.$('input[name="test_questions"]').val());
            var $test_questions_min = parseInt(this.$('input[name="test_questions"]').attr('min'));
            var $test_questions_max = parseInt(this.$('input[name="test_questions"]').attr('max'));

            if ((!$test_questions) || ($test_questions > $test_questions_max) || ($test_questions < $test_questions_min)){ $test_questions = -1;}

            helper
                .callHandler(this.model.id, 'modify_test', {test_id: $test_id, test_questions: $test_questions} )
                .then(
                    function () {
                        view.switchBack();
                    },
                    function (error) {
                        var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    }
                ).done();
        }
    });
});
