define(['require', 'backbone', 'assets/js/url', 'assets/js/templates', 'assets/js/block_model'],
       function (require, Backbone, helper, templates, BlockModel) {

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
                    $content = $block.find('div.content'),
                    View;


                require(['block!' + type], function (Views) {
                    View = Views && Views.student;
                    if (View) {
                        self.children[id] = new View({el: $content, block_id: id});
                    }
                });
            });
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

                require(['block!' + block_type], function (Views) {
                    var view;
                    self.children[block_id] = view = new Views.author({model: model});
                    console.log(view);
                    self.$(".block-content").html(view.render().el);
                });
            //});

            /*
            helper.getView(block_id, 'author').then(function (data) {
                self.$(".content").html(data);

                console.log(templates(block_type).views.author);


                //block.remove();
                //this.children[block_id];
            });
            */
        }
    });

    return SectionView;
});
