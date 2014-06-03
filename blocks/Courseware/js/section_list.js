define(['backbone', 'assets/js/url', 'assets/js/templates',  'assets/js/i18n', 'assets/js/block_model', './edit_structure'],
       function (Backbone, helper, templates, i18n, BlockModel, EditView) {

    'use strict';

    return Backbone.View.extend({

        events: {
            "click .add-section":       "addStructure",
            "click .init-sort-section": "initSorting",
            "click .stop-sort-section": "stopSorting"
        },

        initialize: function() {
            this.listenTo(Backbone, 'modeswitch', this.stopSorting, this);
        },

        render: function() {
            return this;
        },

        postRender: function() {
            this.$el.tooltip({
                items: "li.section",
                content: function() { return _.escape(jQuery(this).find("a").attr("data-title")); },
                show: false,
                hide: false,
                position: {
                    my: "center bottom-10",
                    at: "center top",
                    using: function (position, feedback) {
                        jQuery(this).css(position);
                        jQuery("<div/>")
                            .addClass(["arrow", feedback.vertical, feedback.horizontal].join(" "))
                            .appendTo(this);
                    }
                }
            });
        },

        addStructure: function (event) {
            var id = this.$el.attr("data-blockid");

            if (id == null) {
                return;
            }

            var model = new BlockModel({ title: i18n("Neuer Abschnitt"), type: 'Section' }),
                view = new EditView({ model: model }),
                insert_point = this.$(".no-content"),
                li_wrapper = view.$el.wrap("<li/>").parent(),
                self = this,
                $controls = this.$('.controls'),
                placeholder_item;

            $controls.hide();
            insert_point.before(li_wrapper);
            view.postRender();

            view.promise()
                .fin(function () {
                    li_wrapper.remove();
                    $controls.show();
                })
                .then(function (model) {
                    placeholder_item = insert_point
                        .before(templates("Courseware", "section", model.toJSON()))
                        .prev()
                        .addClass("loading");

                    return self._addStructure(id, model);
                })
                .done(
                    function (data) {
                        placeholder_item.replaceWith(templates("Courseware", "section", data));
                    },
                    function (error) {
                        placeholder_item && placeholder_item.remove();

                        if (error) {
                            alert("ERROR: "  + JSON.stringify(error));
                        }
                    });
        },

        _addStructure: function (parent_id, model) {
            var data = {
                parent: parent_id,
                title:  model.get("title")
            };
            return helper.callHandler(this.model.id, 'add_structure', data);
        },

        _sortable: null,
        _original_positions: null,

        _get_positions: function () {
            return this.$el.sortable("toArray", { attribute: "data-blockid" });
        },

        initSorting: function (event) {
            if (this._sortable) {
                throw "Already sorting!";
            }

            this._sortable = this.$el;
            this._sortable.sortable({
                items:       ".section",
                handle:      ".handle",
                containment: "parent",
                distance:    5
            });

            this._original_positions = this._get_positions();
            this.$el.addClass("sorting");
        },

        stopSorting: function (event) {

            if (!this._sortable) {
                return;
            }

            var positions = this._get_positions(),
                subchapter_id = this._sortable.attr("data-blockid"),
                data;

            this._sortable.sortable("destroy");

            if (JSON.stringify(positions) !== JSON.stringify(this._original_positions)) {
                data = {
                    parent:    subchapter_id,
                    positions: positions
                };

                helper.callHandler(this.model.id, "update_positions", data);
            }

            this._sortable = null;
            this._original_positions = null;
            this.$el.removeClass("sorting");
        }
    });
});
