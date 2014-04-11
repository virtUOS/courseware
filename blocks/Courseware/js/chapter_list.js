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
            "click .subchapter > .title .trash": "destroyStructure"
        },

        initialize: function() {

        },

        render: function() {
            return this;
        },

        postRender: function() {
        },

        addStructure: function (event) {
            var courseware = this,
                $button = jQuery(event.target),
                $parent = $button.closest("[data-blockid]"),
                id = $parent.attr("data-blockid");

            if (id == null) {
                return;
            }

            var model = this._newBlockFromButton($button),
                view = new EditView({ model: model }),

                insert_point = $button.closest(".controls").prev(".no-content"),
                tag = "<" + insert_point[0].tagName + "/>",
                li_wrapper = view.$el.wrap(tag).parent();

            $button.hide();
            insert_point.before(li_wrapper);
            view.focus();

            view.promise()
                .then(
                    function (model) {
                        view.$el.addClass("loading");
                        return courseware._addStructure(id, model);
                    })
                .then(
                    function (data) {
                        /*
                        var new_item = templates("Courseware", model.get("type"), data);
                        insert_point.before(new_item);
                        debugger;
                         */
                        helper.reload();
                    })
                .then(
                    null,
                    function (error) {
                        // TODO:  show error somehow
                        alert(error);
                        view.remove();
                        $button.show();
                    });
        },

        _newBlockFromButton: function ($button) {
            var type;

            if ($button.hasClass("add-chapter")) {
                type = "chapter";
            } else if ($button.hasClass("add-subchapter")) {
                type = "subchapter";
            } else if ($button.hasClass("add-section")) {
                type = "section";
            }

            var titles = {
                chapter:    i18n("Neues Kapitel"),
                subchapter: i18n("Neues Unterkapitel"),
                section:    i18n("Neuer Abschnitt")
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
                $title = $parent.find("> .title"),
                title = $title.find("a").text().trim();

            if (id == null) {
                return;
            }

            // TODO
            var type = this._getType($parent);
            if (!type) {
                throw "ERROR";
            }

            var model = new BlockModel({ id: id, type: type, title: title }),
                view = new EditView({ model: model });

            $title.hide().before(view.el);
            view.focus();

            view.promise()
                .then(
                    function (model) {
                        $title.find("a").text(model.get("title"));
                    },
                    function (error) {
                        alert("TODO:" + error);
                    }
                )
                .always(
                    function () {
                        view.remove();
                        $title.show();
                    });
        },

        _getType: function (element) {
            return _.find(["chapter", "subchapter"], function (type) { return element.hasClass(type); });
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
        }

    });
});
