define(['require', 'backbone', 'assets/js/url', 'assets/js/templates', 'assets/js/block_model', 'assets/js/block_types'],
       function (require, Backbone, helper, templates, BlockModel, block_types) {

    'use strict';

    var SectionView = Backbone.View.extend({

        children: {},

        events: {
            "click button.author": "switchBlock"
        },

        initialize: function() {
            var self = this;

            _.each(this.$('section.block'), function (block) {
                var $block = $(block),
                    id = $block.attr("data-id"),
                    type = $block.attr("data-type"),
                    $content = $block.find('div.content');

                self.children[id] = block_types.get(type).createView('student', {el: $content, block_id: id});
            });
        },

        remove: function() {
            Backbone.View.prototype.remove.call(this);
            _.invoke(this.children, "remove");
        },

        render: function() {
        },

        switchBlock: function (event) {
            var $block = $(event.target).closest(".block"),
                block_id = $block.attr("data-id"),
                block_type = $block.attr("data-type"),
                block_view = this.children[block_id],
                self = this;

            var model = new BlockModel({ id: block_id });

            //model.fetch().then(function (data) {
            block_view.remove();

            var view = block_types.get(block_type).createView("author", {model: model});
            self.$(".block-content").html(view.render().el);
            //});
        }
    });

    return SectionView;
});
