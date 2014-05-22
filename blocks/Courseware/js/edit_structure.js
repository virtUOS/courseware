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
            var template = templates("Courseware", "edit_structure", this.model.toJSON());
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
