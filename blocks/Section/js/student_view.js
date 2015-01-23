define(['backbone', 'q', 'assets/js/student_view', 'assets/js/block_model', 'assets/js/block_types', 'assets/js/url', 'assets/js/i18n', 'assets/js/templates', './edit_view', 'assets/js/tooltip'],
       function (Backbone, Q, StudentView, BlockModel, block_types, helper, i18n, templates, EditView, tooltip) {

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
            _.each(jQuery('section.block'), function (element) {
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

            tooltip(this.$el, 'button.edit,button.trash');
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

            if (confirm("Wollen Sie den Block wirklich löschen?")) {

                $block_wrapper.addClass("loading");

                helper.callHandler(this.model.id, 'remove_content_block', { child_id: block_id })
                    .then(
                        function () {
                            block_view.remove();
                            delete self.children[block_id];
                            $block_wrapper.remove();
                        },

                        function (error) {
                            $block_wrapper.removeClass("loading");
                            var errorMessage = 'Could not update the block: '+jQuery.parseJSON(error.responseText).reason;
                            alert(errorMessage);
                            console.log(errorMessage, arguments);
                        }
                    )
                    .always(function () {
                        self.refreshBlockTypes(self.model.id, self.$('div.block-types'));
                    })
                    .done();
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
            }).done();
        },

        addNewBlock: function (event) {

            var view = this,
                $button = jQuery(event.target),
                block_type = $button.attr("data-blocktype"),
                block_sub_type = $button.attr("data-blocksubtype");

            $button.prop("disabled", true).addClass("loading");

            helper
                .callHandler(this.model.id, 'add_content_block', { type: block_type, sub_type: block_sub_type })

                .then(

                    function (data) {
                        var model = new BlockModel(data),
                            view_name = model.get("editable") ? "author" : "student",
                            block_stub = view.appendBlockStub(model, view_name),
                            $el = block_stub.$el.closest("section.block"),
                            block_name = $button.html();

                        $el.addClass("loading");
                        block_stub.renderServerSide().then(function () {
                            $el.removeClass("loading");

                            // hide the edit button when the form is shown
                            $el.find(".controls button.author").hide();
                            //insert block name
                            $el.find(".controls span.type").html(block_name);
                        }).done();
                    },

                    function (error) {
                        var errorMessage = 'Could not add the block: '+jQuery.parseJSON(error.responseText).reason;
                        alert(errorMessage);
                        console.log(errorMessage, arguments);
                    }
                )

                .always(function () {
                    $button.prop("disabled", false).removeClass("loading");
                    view.refreshBlockTypes(view.model.id, view.$('div.block-types'));
                })
                .done();
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

        editSection: function () {
            var $title = this.$("> .title"),
                view = new EditView({ model: this.model }),
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

                    return false;
                })
                .done(
                    function () {
                        $title.removeClass("loading");
                    },
                    function (error) {
                        $title.removeClass("loading");
                        updateSectionTitle(self.model.revert());
                        if (error) {
                            var errorMessage = 'Could not update the section: '+jQuery.parseJSON(error.responseText).reason;
                            alert(errorMessage);
                            console.log(errorMessage, arguments);
                        }
                    });
        },

        destroySection: function () {

            if (confirm(i18n("Wollen Sie den gesamten Abschnitt wirklich löschen?"))) {
                jQuery("#courseware").addClass("loading");

                var parent_id = this.model.get("parent_id");

                this.model.destroy()
                    .done(
                        function () {
                            helper.navigateTo(parent_id);
                        },
                        function (error) {
                            var errorMessage = 'Could not remove the section: '+jQuery.parseJSON(error.responseText).reason;
                            alert(errorMessage);
                            console.log(errorMessage, arguments);
                            jQuery('#courseware').removeClass('loading');
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

            var new_positions = getBlockPositions(self.$el);
            var thisid = parseInt(protagonist.attr('data-blockid'));
            var index = new_positions.indexOf(thisid);
            new_positions[index] = new_positions[index + 1];
            new_positions[index + 1] = thisid;

            var courseware_id = jQuery("#courseware").attr("data-blockid");
            var data = { parent:    self.model.id, positions: new_positions };
            helper
                .callHandler(courseware_id, "update_positions", data)
                .then(function () {
                    Q($.when(protagonist.effect("blind", { direction: "up" })))

                        // blind up completed
                        .then(function () {
                            return scrollTo(antagonist, { duration: 200, over: 1 });
                        })
                        .done(function () {
                            antagonist.after(protagonist);
                            protagonist.toggle("blind");
                        });
                }).done();
        },

        raiseBlock: function (event) {
            var protagonist = findBlockForEvent(event),
                antagonist = protagonist.prev(".block"),
                self = this;

            // cannot raise first block
            if (!antagonist.length) {
                return;
            }

            var scrollTo = function (to, opts) {
                var deferred = Q.defer();
                $.scrollTo(to, _.extend(opts, { onAfter: function () { deferred.resolve("ok"); } }));
                return deferred.promise;
            };

            var new_positions = getBlockPositions(self.$el);
            var thisid = parseInt(protagonist.attr('data-blockid'));
            var index = new_positions.indexOf(thisid);
            new_positions[index] = new_positions[index - 1];
            new_positions[index - 1] = thisid;

            var courseware_id = jQuery("#courseware").attr("data-blockid");
            var data = { parent:    self.model.id, positions: new_positions };
            helper
                .callHandler(courseware_id, "update_positions", data)
                .then( function () {
                    Q($.when(protagonist.effect("blind", { direction: "up" })))

                        // blind up completed
                        .then(function () {
                            return scrollTo(antagonist, { duration: 200, offset: -50 });
                        })
                        .done(function () {
                            antagonist.before(protagonist);
                            protagonist.toggle("blind");
                        });
                }).done();
        },

        refreshBlockTypes: function (sectionId, container) {
            var model = { id: sectionId };
            var options = { el: container, model: model };
            var section = block_types.findByName('Section');
            var blockTypesStub = section.createView('block_types', options);
            blockTypesStub.renderServerSide();
        }
    });
});
