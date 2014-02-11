define(['module', 'require', 'backbone'], function (module, require, Backbone) {

    'use strict';

    var BLOCK_TYPES = module.config().block_types;


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
                    type = $block.attr("data-type");

                require(["blocks/" + type + "/js/" + type], function (BlockView) {
                    self.children.push(new BlockView({el: block, block_id: id}));
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
