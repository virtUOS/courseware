define(['backbone', 'assets/js/url'], function (Backbone, urlhelper) {

    'use strict';

    var StudentView = Backbone.View.extend({

        events: {
            "click div.content": function (event) {
                  var div = $(event.target),
                      content = div.text();
                  div.after($("<textarea></textarea>").val(content)).hide().next().focus();
            },
            "blur textarea": function (event) {
                var textarea = $(event.target),
                    div = textarea.prev(),
                    old_val = div.text(),
                    new_val = textarea.val();

                // remove the textarea, put the new content into the div and show it
                textarea.remove();
                div.text(new_val).show();

                urlhelper
                    .callHandler(this.block_id, "foo", {content: new_val})
                    .then(
                        // success
                        null,
                        /* function (data) { console.log("success", arguments); }, */

                        // error
                        function () {
                            div.text(old_val);

                            alert("Fehler, TODO!");
                            console.log("fail", arguments, div, old_val);
                        });


            }

        },

        initialize: function(options) {
            console.log("HTMLBlock initialized");
            this.block_id = options.block_id;
        },

        render: function() {
        }
    });

    return StudentView;
});
