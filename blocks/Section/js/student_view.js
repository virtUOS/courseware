define(['assets/js/block_view', 'assets/js/block_model', 'assets/js/block_types', 'assets/js/url'],
       function (BlockView, BlockModel, blockTypes, helper) {

    'use strict';

    var SectionView = BlockView.extend({

        // TODO: put this into the super 'class'
        view_name: "student",

        children: {},

        events: {
            "click button.author": "switchToAuthorView",
            "click .add-block-type": "addNewBlock"
        },

        initialize: function() {
            var self = this;

            _.each(this.$('section.block'), function (block) {
                var $block = $(block),
                    id     = $block.attr("data-id"),
                    type   = $block.attr("data-type"),
                    $el    = $block.find('div.block-content'),
                    model  = new BlockModel({ id: id, type: type });

                self.children[id] = blockTypes.get(type).createView('student', {el: $el, model: model});
                self.listenTo(self.children[id], 'switch', _.bind(self.switchView, self, id));
            });
        },

        remove: function() {
            BlockView.prototype.remove.call(this);
            _.invoke(this.children, "remove");
        },

        render: function() {
            return this;
        },

        switchView: function (block_id, view_name) {

            var block_view = this.children[block_id],
                model = block_view.model,
                $block_wrapper = block_view.$el.closest('section.block');

            // TODO: switch on view_name!!
            $block_wrapper.find(".controls button.author").toggle();

            block_view.remove();

            // create new view
            var el = $("<div class='block-content loading'/>");
            $block_wrapper.append(el);

            var view = blockTypes
                    .get(model.get('type'))
                    .createView(view_name, {
                        el: el,
                        model: model
                    });

            this.children[block_id] = view;
            this.listenTo(view, "switch", _.bind(this.switchView, this, block_id));

            view.renderServerSide().then(function () {
                el.removeClass("loading");
            });
        },

        switchToAuthorView: function (event) {
            var id = $(event.target).closest(".block").attr("data-id");
            this.switchView(id, "author");
        },

        addNewBlock: function (event) {
            var block_type = $(event.target).attr("data-type");
            helper.callHandler(this.model.id, 'add_child', { type: block_type }).then(

                function (data) {
                    alert("TODO");
                    window.location = "";
                },

                function (error) {
                    alert("TODO: could not add block");
                }
            );

        }
    });

    return SectionView;
});
