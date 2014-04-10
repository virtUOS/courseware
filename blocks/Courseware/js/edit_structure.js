define(['backbone', 'assets/js/url', 'assets/js/templates'],
       function (Backbone, helper, templates) {

    'use strict';

    return Backbone.View.extend({

        className: "edit-structure",

        events: {
            "submit form": "submit"
        },

        deferred: null,

        initialize: function() {
            this.deferred = jQuery.Deferred();
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
            return this.deferred.promise();
        },

        submit: function (event) {

            event.preventDefault();

            var old = this.model.get("title"),
                val = this.$("input").val().trim(),
                self = this;

            if (val === old) {
                self.deferred.resolve(self.model);
                return;
            }

            this.model.set("title", val);
            this.$el.addClass("loading");

            helper
                .putView(this.model.id, this.model.toJSON())
                .then(
                    // TODO: what to do with data?
                    function (data) {
                        self.deferred.resolve(self.model);
                    },

                    // TODO: what to do? show error? or just remove it?
                    function (error) {
                        self.deferred.reject(error);
                    }
                );
        }
    });
});
