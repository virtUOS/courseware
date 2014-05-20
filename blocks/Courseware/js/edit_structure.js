define(['q', 'backbone', 'assets/js/templates'],
       function (Q, Backbone, templates) {

    'use strict';

    return Backbone.View.extend({

        className: "edit-structure",

        events: {
            'submit form':         'submit',
            'click button.cancel': 'cancel'
        },

        deferred: null,

        initialize: function() {
            this.deferred = Q.defer();
            this.render();
        },

        render: function () {
            var template = templates("Courseware", "edit_structure", this.model.toJSON());
            this.$el.html(template);
            return this;
        },

        focus: function () {
            this.$("input").get(0).focus();
        },

        promise: function () {
            return this.deferred.promise;
        },

        submit: function (event) {
            event.preventDefault();
            var new_title = this.$("input").val().trim();

            if (new_title == '') {
                return;
            }

            this.model.set("title", new_title);
            this.deferred.resolve(this.model);
        },

        cancel: function () {
            this.deferred.reject();
        }
    });
});
