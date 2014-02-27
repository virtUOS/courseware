define(['assets/js/author_view', 'assets/js/url'],
       function (AuthorView, helper) {

    'use strict';

    return AuthorView.extend({

        events: {
            "click button": function (event) {
                var textarea = this.$("textarea"),
                    new_val = textarea.val(),
                    view = this;

                //textarea.remove();
                helper
                    .callHandler(this.model.id, "foo", {content: new_val})
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
