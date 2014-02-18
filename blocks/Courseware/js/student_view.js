define(['require', 'backbone', 'assets/js/blocks'], function (require, Backbone, blocks) {

    'use strict';

    var Courseware = Backbone.View.extend({

        children: [],

        events: {
            "click li.chapter": "debug"
        },

        initialize: function() {
            var self = this;

            _.each(this.$('section.block'), function (block) {
                var $block = $(block),
                    id = $block.attr("data-id"),
                    type = $block.attr("data-type"),
                    View;

                blocks(type, function (views) {
                    View = views && views.student;
                    if (View) {
                        self.children.push(new View({el: block, block_id: id}));
                    }
                });
            });
        },

        render: function() {
        },

        debug: function (event) {
            alert($(event.target).text());
        }
    });

    return Courseware;
});
