define(['backbone', 'assets/js/student_view', 'assets/js/block_model', 'assets/js/block_types', 'assets/js/url', 'assets/js/i18n', 'assets/js/templates', './edit_view'],
       function (Backbone, StudentView, BlockModel, blockTypes, helper, i18n, templates, EditView) {

    'use strict';

    return StudentView.extend({

        children: {},

        events: {
            "click .title .edit":     "editSection",
            "click .title .trash":    "destroySection",

            "click .add-block-type":  "addNewBlock",

            "click .init-sort-block": "initSorting",
            "click .stop-sort-block": "stopSorting",

            // child block stuff

            "click .block .author":   "switchToAuthorView",
            "click .block .trash":    "destroyView"
        },

        initialize: function() {
            _.each(jQuery('section.block'), function (element, index, list) {
                this.initializeBlock(element, undefined, "student");
            }, this);

            this.listenTo(Backbone, "modeswitch", this.switchMode, this);
        },

        remove: function() {
            StudentView.prototype.remove.call(this);
            _.invoke(this.children, "remove");
        },

        render: function() {
            return this;
        },

        postRender: function() {
            _.each(this.children, function (block) {
                if (typeof block.postRender === "function") {
                    block.postRender();
                }
            });
        },

        switchMode: function (view) {
            if (view === "student") {

                _.each(this.children, function (child, child_id) {
                    this.switchView(child_id, view);
                }, this);

                this.stopSorting();
            }
        },

        switchToAuthorView: function (event) {
            var id = jQuery(event.target).closest(".block").attr("data-blockid");
            this.switchView(id, "author");
        },

        destroyView: function (event) {
            var block_id = jQuery(event.target).closest(".block").attr("data-blockid"),
                block_view = this.children[block_id],
                $block_wrapper = block_view.$el.closest('section.block'),
                self = this;

            if (confirm("Wollen Sie wirklich löschen?")) {

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
            }
        },


        switchView: function (block_id, view_name) {

            var block_view = this.children[block_id],
                model = block_view.model,
                $block_wrapper = block_view.$el.closest('section.block');


            // already switched
            if (block_view.view_name === view_name) {
                return;
            }


            // TODO: switch on view_name!!
            $block_wrapper.find(".controls button.author").toggle();

            block_view.remove();

            // create new view
            var el = jQuery("<div class='block-content'/>");
            $block_wrapper.append(el).addClass("loading");

            var view = blockTypes
                    .get(model.get('type'))
                    .createView(view_name, {
                        el: el,
                        model: model
                    });

            this.addBlock(view);

            view.renderServerSide().then(function () {
                $block_wrapper.removeClass("loading");
            });
        },

        addNewBlock: function (event) {

            var view = this,
                $button = jQuery(event.target),
                block_type = $button.attr("data-blocktype");

            $button.prop("disabled", true).addClass("loading");

            helper
                .callHandler(this.model.id, 'add_content_block', { type: block_type })

                .then(

                    function (data) {
                        var model = new BlockModel(data),
                            view_name = model.get("editable") ? "author" : "student",
                            block_stub = view.appendBlockStub(model, view_name),
                            $el = block_stub.$el.closest("section.block");

                        $el.addClass("loading");
                        block_stub.renderServerSide().then(function () {
                            $el.removeClass("loading");

                            // hide the edit button when the form is shown
                            $el.find(".controls button.author").hide();
                        });
                    },

                    function (error) {
                        alert("TODO: could not add block");
                    }
                )

                .always(function () {
                    $button.prop("disabled", false).removeClass("loading");
                });
        },

        appendBlockStub: function (model, view_name) {
            var block_wrapper = templates("Section", "block_wrapper", model.toJSON()),
                block_el = this.$(".no-content").before(block_wrapper).prev();

            return this.initializeBlock(block_el, model, view_name);
        },

        initializeBlock: function (block, model, view_name) {
            var $block = jQuery(block),
                $el    = $block.find('div.block-content'),
                view;

            if (!_.isObject(model)) {
                model  = new BlockModel({
                    id:   $block.attr("data-blockid"),
                    type: $block.attr("data-blocktype")
                });
            }

            view = blockTypes
                .get(model.get('type'))
                .createView(view_name, {el: $el, model: model});

            return this.addBlock(view);
        },

        addBlock: function (block) {
            this.children[block.model.id] = block;
            this.listenTo(block, "switch", _.bind(this.switchView, this, block.model.id));

            return block;
        },

        editSection: function (event) {
            var $title = this.$("> .title"),
                view = new EditView({ model: this.model }),
                orig_model = this.model.clone(),
                $wrapped = $title.wrapInner("<div/>").children().first(),
                self = this,
                updateSectionTitle = function (model) {
                    var new_title = templates("Section", "title", model.toJSON());
                    $title.replaceWith(new_title);
                    return self.$("> .title");
                };

            $wrapped.hide().before(view.el);

            view.focus();

            view.promise()
                .fin(function () {
                    view.remove();
                    $wrapped.children().unwrap();
                })
                .then(function (model) {
                    if (model.hasChanged()) {
                        $title = updateSectionTitle(model).addClass("loading");
                        return model.save();
                    }
                })
                .done(
                    function () {
                        $title.removeClass("loading");
                    },
                    function (error) {
                        $title.removeClass("loading");
                        updateSectionTitle(self.model.revert());
                        if (error) {
                            alert("Fehler: "  + JSON.stringify(error));
                        }
                    });
        },

        destroySection: function (event) {

            if (confirm(i18n("Wollen Sie wirklich löschen?"))) {
                jQuery("#courseware").addClass("loading");

                var parent_id = this.model.get("parent_id");

                helper
                    .deleteView(this.model.id)
                    .then(
                        function () {
                            helper.navigateTo(parent_id);
                        },
                        function (error) {
                            console.log(arguments);
                            alert("ERROR:" + error);
                        });

            }
        },

        _original_positions: null,

        _get_positions: function () {
            return this.$el.sortable("toArray", { attribute: "data-blockid" });
        },

        _hack_metadata: null,

        initSorting: function (event) {

            // HACK: does not work without ;_;
            this._hack_metadata = jQuery.metadata;
            jQuery.metadata = null;

            this.$el.sortable({
                items:    "section.block",
                axis:     "y",
                distance: 5
            });

            this._original_positions = this._get_positions();
            this.$(".block-controls button").toggle();
        },

        stopSorting: function () {

            var positions, courseware_id, data;

            if (this._original_positions === null) {
                return;
            }

            positions = this._get_positions();
            courseware_id = jQuery("#courseware").attr("data-blockid"),

            this.$el.sortable("destroy").find(".block-controls button").toggle();

            // HACK: does not work without ;_;
            jQuery.metadata = this._hack_metadata;

            if (JSON.stringify(positions) !== JSON.stringify(this._original_positions)) {
                data = {
                    parent:    this.model.id,
                    positions: positions
                };

                helper.callHandler(courseware_id, "update_positions", data);
            }

            this._original_positions = null;
        }
    });
});
