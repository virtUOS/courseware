define(['backbone', 'assets/js/url'], function (Backbone, helper) {
    return Backbone.View.extend({

        // filled by block type's createView method
        block_type: null,

        initializeFromDOM: function () {
        },

        renderServerSide: function () {
            var self = this;

            return helper.getView(this.model.id, this.view_name)
                .then(
                    function (data) {
                        self.$el.html(data);

                        // let the block initialize from the just
                        // inserted DOM
                        self.initializeFromDOM();

                        if (typeof self.postRender === "function") {
                            self.postRender();
                        }
                    }
                );
        }
    });
});
