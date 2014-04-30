define(['assets/js/author_view', 'assets/js/url'], function (AuthorView, helper) {
    'use strict';

    return AuthorView.extend({

        events: {
            "click button[name=save]":   "onSave",
            "click button[name=cancel]": "switchBack"
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        },

        postRender: function () {
            var autoCompletionUrl = jQuery('input[name="suggestion_url"]', this.$el).val();
            var idField = jQuery('input[name="test_id"]', this.$el);
            var nameField = jQuery('input[name="test_name"]', this.$el);

            nameField.autocomplete({
                source: function (request, response) {
                    jQuery.post(autoCompletionUrl, { term: request.term }, function (data) {
                        response(data);
                    }, 'json');
                },
                select: function (event, ui) {
                    idField.val(ui.item.value);
                    nameField.val(ui.item.label);

                    event.preventDefault();
                }
            });

            return this;
        },

        onSave: function () {
            var view = this;

            helper
                .callHandler(this.model.id, 'modify_test', this.$('input[name="test_id"]').val())
                .then(
                    function () {
                        view.switchBack();
                    },
                    function () {
                        alert('test modification failed');
                    }
                );
        }
    });
});
