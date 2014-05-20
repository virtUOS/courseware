define(['q', 'backbone', 'assets/js/url', 'assets/js/templates'],
       function (Q, Backbone, helper, templates) {

    'use strict';

    return Backbone.View.extend({

        className: "edit-structure",

        events: {
            'click button.cancel': 'cancel',
            "submit form": "submit"
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
            return;

            /*
            // add new object, but title is not the default title
            if (!this.model.id) {
                self.deferred.resolve(self.model);
                return;
            }

            this.$el.addClass("loading");

            helper
                .putView(this.model.id, this.model.toJSON())
                .then(
                    function (data) {
                        // TODO: what to do with data?
                        // self.model.set(data);
                        self.deferred.resolve(self.model);
                    },

                    function (error) {
                        self.deferred.reject(error);
                    }
                );
             */
        },

        cancel: function () {
            this.deferred.reject();
        }
    });
});
