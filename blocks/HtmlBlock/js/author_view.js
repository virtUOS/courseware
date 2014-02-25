define(['backbone', 'assets/js/url', 'assets/js/templates'], function (Backbone, helper, templates) {

    'use strict';

    var AuthorView = Backbone.View.extend({

        events: {
            "click button": function (event) {
                var textarea = this.$("textarea"),
                    new_val = textarea.val();

                //textarea.remove();
                helper
                    .callHandler(this.model.id, "foo", {content: new_val})
                    .then(
                        // success
                        function () {
                            $(event.target).addClass("accept");
                        },

                        // error
                        function () {
                            alert("Fehler, TODO!");
                            console.log("fail", arguments);
                        });
            }

        },

        initialize: function(options) {
        },

        render: function() {
            var self = this;
            helper
                .getView(this.model.id, "author")
                .then(
                    function (data) { self.$el.html(data); }
                );
            return this;
        }
    });

    return AuthorView;
});
