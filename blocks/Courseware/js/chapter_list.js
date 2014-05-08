define(['backbone', 'assets/js/url', 'assets/js/templates',  'assets/js/i18n', 'assets/js/block_model', './edit_structure'],
       function (Backbone, helper, templates, i18n, BlockModel, EditView) {

    'use strict';

    return Backbone.View.extend({

        events: {
            "click .add-chapter":               "addStructure",
            "click .add-subchapter":            "addStructure",

            "click .chapter    > .title .edit": "editStructure",
            "click .subchapter > .title .edit": "editStructure",

            "click .chapter    > .title .trash": "destroyStructure",
            "click .subchapter > .title .trash": "destroyStructure",

            "click .init-sort-chapter":    "initSorting",
            "click .init-sort-subchapter": "initSorting",

            "click .stop-sort-chapter":    "stopSorting",
            "click .stop-sort-subchapter": "stopSorting"
        },

        initialize: function() {

        },

        render: function() {
            return this;
        },

        postRender: function() {
        },

        addStructure: function (event) {
            var $button = jQuery(event.target),
                id = $button.closest("[data-blockid]").attr("data-blockid");

            if (id == null) {
                return;
            }

            var model = this._newBlockFromButton($button),
                view = new EditView({ model: model }),

                insert_point = $button.closest(".controls").prev(".no-content"),
                tag = "<" + insert_point[0].tagName + "/>",
                li_wrapper = view.$el.wrap(tag).parent(),
                courseware = this,
                placeholder_item;

            $button.hide();
            insert_point.before(li_wrapper);
            view.focus();

            view.promise()
                .fin(function () {
                    view.remove();
                    $button.fadeIn();
                })
                .then(function (model) {
                    placeholder_item = insert_point
                        .before(templates("Courseware",
                                          model.get("type"),
                                          model.toJSON()))
                        .prev()
                        .addClass("loading");

                    return courseware._addStructure(id, model);
                })
                .done(
                    function (data) {
                        placeholder_item.replaceWith(
                            templates("Courseware", model.get("type"), data));
                    },
                    function (error) {
                        placeholder_item && placeholder_item.remove();
                        if (error) {
                            alert("Fehler: "  + JSON.stringify(error));
                        }
                    });
        },

        _newBlockFromButton: function ($button) {
            var type;

            if ($button.hasClass("add-chapter")) {
                type = "chapter";
            } else if ($button.hasClass("add-subchapter")) {
                type = "subchapter";
            }

            var titles = {
                chapter:    i18n("Neues Kapitel"),
                subchapter: i18n("Neues Unterkapitel")
            };
            return new BlockModel({ title: titles[type], type: type });
        },

        _addStructure: function (parent_id, model) {
            var data = {
                parent: parent_id,
                title:  model.get("title")
            };
            return helper.callHandler(this.model.id, 'add_structure', data);
        },

        editStructure: function (event) {
            var $parent = jQuery(event.target).closest("[data-blockid]"),
                id = $parent.attr("data-blockid"),
                type = this._getType($parent);

            if (id == null) {
                return;
            }

            if (!type) {
                throw "ERROR";
            }

            var $title = $parent.find("> .title"),
                title = $title.find("a").text().trim(),
                model = new BlockModel({ id: id, type: type, title: title }),
                orig_model = model.clone(),
                view = new EditView({ model: model }),
                updateListItem = function (model) {
                    $title.find("a").text(model.get('title'));
                };

            $title.hide().before(view.el);
            view.focus();

            view.promise()
                .fin(function () {
                    view.remove();
                    $title.show();
                })
                .then(function (model) {
                    $parent.addClass("loading");
                    if (model.hasChanged()) {
                        updateListItem(model);
                        return model.save();
                    }
                })
                .done(
                    function () {
                        $parent.removeClass("loading");
                    },
                    function (error) {
                        $parent.removeClass("loading");
                        updateListItem(orig_model);
                        if (error) {
                            alert("Fehler: "  + JSON.stringify(error));
                        }
                    });
        },

        _getType: function (element) {
            return element.is("#courseware") ? "courseware"
                : _.find([, "chapter", "subchapter"], function (type) { return element.hasClass(type); });
        },

        destroyStructure: function (event) {

            var courseware = this,
                $button = jQuery(event.target),
                $parent = $button.closest("[data-blockid]"),
                id = $parent.attr("data-blockid");

            if (id == null) {
                return;
            }

            if (confirm(i18n("Wollen Sie wirklich löschen?"))) {
                $parent.addClass("loading");

                helper
                    .deleteView(id)
                    .then(
                        function () {
                            helper.reload();
                        },
                        function (error) {
                            console.log(arguments);
                            alert("ERROR:" + error);
                        });
            }
        },

        _sortable: null,
        _original_positions: null,

        _get_positions: function () {
            return this._sortable.sortable("toArray", { attribute: "data-blockid" });
        },

        initSorting: function (event) {
            var child_types = { courseware: "chapter", chapter: "subchapter" },
                child_type = child_types[this._getType(jQuery(event.target).closest("[data-blockid]"))];

            if (this._sortable) {
                throw "Already sorting!";
            }

            if (child_type === "chapter") {
                this._sortable = this.$el;
            } else {
                this._sortable = this.$(".subchapters");
            }

            this._sortable.sortable({
                items:    "." + child_type,
                handle:   ".handle",
                axis:     "y",
                distance: 5,
                opacity:  0.7,
                helper:   function (event, element) {
                    return element.clone().find(".subchapters, .controls").remove().end();
                }
            });

            this._original_positions = this._get_positions();
            this.$el.addClass("sorting");
        },

        stopSorting: function (event) {

            var positions = this._get_positions(),
                parent_id = jQuery(event.target).closest("[data-blockid]").attr("data-blockid"),
                data;

            this._sortable.sortable("destroy");

            if (JSON.stringify(positions) !== JSON.stringify(this._original_positions)) {
                data = {
                    parent:    parent_id,
                    positions: positions
                };
                helper.callHandler(this.model.id, "update_positions", data);
            }

            this._original_positions = this._sortable = null;
            this.$el.removeClass("sorting");
        }
    });
});
