define(['assets/js/student_view', 'assets/js/url', 'assets/js/templates'], function (StudentView, helper, templates) {

    'use strict';

    return StudentView.extend({
        events: {
            'change input[type=checkbox]': 'onConfirm'
        },

        initialize: function(options) {
        },

        initializeFromDOM: function () {
            // complete model by retrieving the attributes from the
            // DOM instead of making a roundtrip to the server
            this.model.set({
                'confirmed': this.$('input[name="confirmed"]').prop('checked'),
                'title':     this.$('.title').html()
            });
        },

        render: function() {
            this.$el.html(templates("ConfirmBlock", 'student_view', _.clone(this.model.attributes)));
            return this;
        },

        onConfirm: function() {
            this.model.set('confirmed', true);
            this.render();

            helper
                .callHandler(this.model.id, 'confirm', {})
                .fail(function (error) {
                    var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                    alert(errorMessage);
                    console.log(errorMessage, arguments);
                })
                .done();
        }
    });
});
