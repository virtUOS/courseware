define(['assets/js/block_view', 'assets/js/url'],
       function (BlockView, helper) {

    'use strict';

    var AuthorView = BlockView.extend({

        // TODO: put this into the super 'class'
        view_name: "author",

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
                            view.trigger("switch", "student");
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

    return AuthorView;
});
