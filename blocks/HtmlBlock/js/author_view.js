define(['backbone', 'assets/js/url', 'assets/js/templates'], function (Backbone, helper, templates) {

    'use strict';

    var AuthorView = Backbone.View.extend({

        events: {
            "click div.content": function (event) {
                  /*
                  var div = $(event.target),
                      content = div.text();
                  div.after($("<textarea></textarea>").val(content)).hide().next().focus();
                   */
            },

            "blur textarea": function (event) {
                /*
                var textarea = $(event.target),
                    div = textarea.prev(),
                    old_val = div.text(),
                    new_val = textarea.val();

                // remove the textarea, put the new content into the div and show it
                textarea.remove();
                div.text(new_val).show();

                helper
                    .callHandler(this.block_id, "foo", {content: new_val})
                    .then(
                        // success
                        null,
                        // function (data) { console.log("success", arguments); },

                        // error
                        function () {
                            div.text(old_val);

                            alert("Fehler, TODO!");
                            console.log("fail", arguments, div, old_val);
                        });

            */
            }

        },

        initialize: function(options) {
            this.block_id = options.block_id;
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
