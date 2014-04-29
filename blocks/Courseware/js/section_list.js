define(['backbone', 'assets/js/url', 'assets/js/templates',  'assets/js/i18n', 'assets/js/block_model', './edit_structure'],
       function (Backbone, helper, templates, i18n, BlockModel, EditView) {

    'use strict';

    return Backbone.View.extend({

        events: {
            "click .add-section": "addStructure"
        },

        initialize: function() {
        },

        render: function() {
            return this;
        },

        postRender: function() {
            this.$el.tooltip({
                items: "li.section",
                content: function() { return _.escape(jQuery(this).find("a").attr("title")); },
                show: false,
                hide: false,
                position: {
                    my: "center bottom-10",
                    at: "center top",
                    using: function (position, feedback) {
                        jQuery(this).css(position);
                        jQuery("<div/>")
                            .addClass("arrow")
                            .addClass(feedback.vertical)
                            .addClass(feedback.horizontal)
                            .appendTo(this);
                    }
                }
            });
        },

        addStructure: function (event) {
            var self = this,
                id = this.$el.attr("data-blockid");

            if (id == null) {
                return;
            }

            var model = new BlockModel({ title: i18n("Neuer Abschnitt"), type: 'Section' }),
                view = new EditView({ model: model }),
                insert_point = this.$(".no-content"),
                li_wrapper = view.$el.wrap("<li/>").parent(),
                new_section;

            this.$(".controls").hide();
            insert_point.before(li_wrapper);
            view.focus();

            view.promise()
                .then(
                    function (model) {
                        li_wrapper.remove();
                        new_section = insert_point.before(templates("Courseware", "section", model.toJSON())).prev().addClass("loading");
                        return self._addStructure(id, model);
                    })
                .then(
                    function (data) {
                        new_section.replaceWith(templates("Courseware", "section", data));
                    })
                .then(
                    null,
                    function (error) {
                        alert("ERROR: "  + JSON.stringify(error));
                        li_wrapper.remove();
                        new_section.remove();
                    })
                .always(function () {
                    this.$(".controls").fadeIn();
                });
        },

        _addStructure: function (parent_id, model) {
            var data = {
                parent: parent_id,
                title:  model.get("title")
            };
            return helper.callHandler(this.model.id, 'add_structure', data);
        }
    });
});
