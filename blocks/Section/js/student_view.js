define(['assets/js/student_view', 'assets/js/block_model', 'assets/js/block_types', 'assets/js/url'],
       function (StudentView, BlockModel, blockTypes, helper) {

    'use strict';

    return StudentView.extend({

        children: {},

        events: {
            "click .block .author":  "switchToAuthorView",
            "click .block .trash":   "destroyView",

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
            });
        },

        remove: function() {
            StudentView.prototype.remove.call(this);
            _.invoke(this.children, "remove");
        },

        render: function() {
            return this;
        },

        switchToAuthorView: function (event) {
            var id = $(event.target).closest(".block").attr("data-id");
            this.switchView(id, "author");
        },

        destroyView: function (event) {
            var block_id = $(event.target).closest(".block").attr("data-id"),
                block_view = this.children[block_id],
                $block_wrapper = block_view.$el.closest('section.block'),
                self = this;

            $block_wrapper.addClass("loading");

            helper.callHandler(this.model.id, 'remove_content_block', { child_id: block_id }).then(

                function (data) {
                    block_view.remove();
                    delete self.children[block_id];
                    $block_wrapper.remove();
                },

                function (error) {
                    $block_wrapper.removeClass("loading");
                    alert("TODO: could not delete block");
                }
            );
        },


        switchView: function (block_id, view_name) {

            var block_view = this.children[block_id],
                model = block_view.model,
                $block_wrapper = block_view.$el.closest('section.block');

            // TODO: switch on view_name!!
            $block_wrapper.find(".controls button.author").toggle();

            block_view.remove();

            // create new view
            var el = $("<div class='block-content'/>");
            $block_wrapper.append(el).addClass("loading");

            var view = blockTypes
                    .get(model.get('type'))
                    .createView(view_name, {
                        el: el,
                        model: model
                    });

            this.children[block_id] = view;
            this.listenTo(view, "switch", _.bind(this.switchView, this, block_id));

            view.renderServerSide().then(function () {
                $block_wrapper.removeClass("loading");
            });
        },

        addNewBlock: function (event) {
            var block_type = $(event.target).attr("data-type");
            helper.callHandler(this.model.id, 'add_content_block', { type: block_type }).then(

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
});
