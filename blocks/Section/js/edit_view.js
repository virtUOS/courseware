define(['q', 'backbone', 'assets/js/templates'],
       function (Q, Backbone, templates) {

    'use strict';

    return Backbone.View.extend({

        className: "edit-section",

        events: {
            'submit form':         'submit',
            'click button.cancel': 'cancel'
        },

        deferred: null,

        initialize: function() {
            this.deferred = Q.defer();
            $('.ui-tooltip').remove();
            this.listenTo(Backbone, "modeswitch", this.cancel, this);
            this.render();
        },

        render: function () {
            var template = templates("Section", "edit_view", this.model.toJSON());
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
            this.model.set("title", new_title);
            this.deferred.resolve(this.model);
        },

        cancel: function () {
            this.deferred.reject();
        }
    });
});
