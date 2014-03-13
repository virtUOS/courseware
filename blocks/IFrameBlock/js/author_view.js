define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button": function (event) {
                var url_input    = this.$("input.urlinput");
                var new_url      = url_input.val();
                var height_input = this.$("input.heightinput");
                var new_height   = height_input.val();
                var view         = this;

                helper
                    .callHandler(this.model.id, "foo", {url: new_url, height: new_height})
                    .then(
                        // success
                        function () {
                            $(event.target).addClass("accept");
                            view.switchBack();
                        },

                        // error
                        function () {
                            alert("Fehler, TODO!");
                            console.log("fail", arguments);
                        });
            }

        },

        initialize: function(options) {
            // console.log("initialize HtmlBlock author view", this, options);
        },

        render: function() {
            return this;
        }
    });
});
