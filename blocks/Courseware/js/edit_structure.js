define(['q', 'backbone', 'assets/js/templates', 'assets/js/i18n'],
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
            this.model.set("placeholder", i18n("Sichtbar ab"));
            var template = templates("Courseware", "edit_structure", this.model.toJSON());
            _(this.addDatePicker).defer();
            this.$el.html(template);
            return this;
        },

        addDatePicker: function() {
            $('input[type=date]').datepicker();
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
