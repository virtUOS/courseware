define(['q', 'backbone', 'assets/js/templates', 'assets/js/i18n', 'dateFormat'],
       function (Q, Backbone, templates, i18n) {

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
            this.listenTo(Backbone, "modeswitch", this.cancel, this);
            this.render();
        },

        render: function () {
            var data = {
                title: this.model.get("title")
            };

            // hide publication_date for sections
            if (this.model.get("type") !== 'Section') {
                if (this.model.get("publication_date")) {
                    data.publication_date =
                        Date.fromUnixtime(this.model.get("publication_date"))
                            .format("Y-m-d");
                }
                data.chapter = true;
            }

            var template = templates("Courseware", "edit_structure", data);
            this.$el.html(template);
            return this;
        },

        postRender: function () {
            if (typeof Modernizr === 'undefined' || !Modernizr.inputtypes.date) {
                $('input[type=date]').datepicker();
            }
            this.$("input").get(0).focus();
        },

        promise: function () {
            return this.deferred.promise;
        },

        submit: function (event) {
            event.preventDefault();
            var new_title = this.$("input").val().trim();
            var new_publication_date = this.$("input[type=date]").val();

            if (new_title == '') {
                return;
            }

            this.model.set("title", new_title);
            this.model.set("publication_date", new_publication_date);
            this.deferred.resolve(this.model);
        },

        cancel: function () {
            this.deferred.reject();
        }
    });
});
