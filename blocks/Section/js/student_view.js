define(['backbone', 'q', 'assets/js/student_view', 'assets/js/block_model', 'assets/js/block_types', 'assets/js/url', 'assets/js/i18n', 'assets/js/templates', './edit_view'],
       function (Backbone, Q, StudentView, BlockModel, block_types, helper, i18n, templates, EditView) {

    'use strict';

    function findBlockForEvent(event) {
        return jQuery(event.target).closest(".block");
    }

    function findBlockIDForEvent(event) {
        return parseInt(findBlockForEvent(event).attr("data-blockid"), 10);
    }

    function getBlockPositions($el) {
        return $el.find(".block").map(function (i, el) {
            return parseInt(jQuery(el).attr("data-blockid"), 10);
        }).toArray();
    }


    function swapPositions(ary, start_index) {
        var result = ary.slice(0);
        if (0 <= start_index && start_index <= result.length - 2) {
            result.splice(start_index, 2, result[start_index + 1], result[start_index]);
        }
        return result;
    }

    return StudentView.extend({

        children: {},

        events: {
            "click .title .edit":     "editSection",
            "click .title .trash":    "destroySection",

            "click .add-block-type":  "addNewBlock",

            // child block stuff

            "click .block .lower":    "lowerBlock",
            "click .block .raise":    "raiseBlock",

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
            }
        },

        switchToAuthorView: function (event) {
            var id = findBlockIDForEvent(event);
            this.switchView(id, "author");
        },

        destroyView: function (event) {
            var block_id = findBlockIDForEvent(event),
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

            var view = block_types
                    .findByName(model.get('type'))
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
                        alert("Could not add block: "+jQuery.parseJSON(error.responseText).reason);
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

            view = block_types
                .findByName(model.get('type'))
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

                this.model.destroy()
                    .done(
                        function () {
                            helper.navigateTo(parent_id);
                        },
                        function (error) {
                            alert("Fehler: "  + JSON.stringify(error));
                        });

            }
        },

        lowerBlock: function (event) {
            var protagonist = findBlockForEvent(event),
                antagonist = protagonist.next(".block"),
                self = this;

            // cannot lower last block
            if (!antagonist.length) {
                return;
            }

            var scrollTo = function (to, opts) {
                var deferred = Q.defer();
                $.scrollTo(to, _.extend(opts, { onAfter: function () { deferred.resolve("ok"); } }));
                return deferred.promise;
            };

            Q($.when(protagonist.effect("blind", { direction: "up" })))

                // blind up completed
                .then(function () {
                    return scrollTo(antagonist, { duration: 200, over: 1 });
                })
                .done(function () {
                    antagonist.after(protagonist);
                    protagonist.toggle("blind");


                    var new_positions = getBlockPositions(self.$el),
                        courseware_id = jQuery("#courseware").attr("data-blockid"),
                        data = {
                            parent:    self.model.id,
                            positions: new_positions
                        };
                    helper.callHandler(courseware_id, "update_positions", data);
                });
        },

        raiseBlock: function (event) {
            var protagonist = findBlockForEvent(event),
                antagonist = protagonist.prev(".block"),
                self = this;

            // cannot lower last block
            if (!antagonist.length) {
                return;
            }

            var scrollTo = function (to, opts) {
                var deferred = Q.defer();
                $.scrollTo(to, _.extend(opts, { onAfter: function () { deferred.resolve("ok"); } }));
                return deferred.promise;
            };

            Q($.when(protagonist.effect("blind", { direction: "up" })))

                // blind up completed
                .then(function () {
                    return scrollTo(antagonist, { duration: 200, offset: -50 });
                })
                .done(function () {
                    antagonist.before(protagonist);
                    protagonist.toggle("blind");


                    var new_positions = getBlockPositions(self.$el),
                        courseware_id = jQuery("#courseware").attr("data-blockid"),
                        data = {
                            parent:    self.model.id,
                            positions: new_positions
                        };
                    helper.callHandler(courseware_id, "update_positions", data);
                });
        }
    });
});
