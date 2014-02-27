define(['backbone', 'assets/js/url'], function (Backbone, helper) {
    return Backbone.View.extend({

        // filled by block type's createView method
        block_type: null,

        renderServerSide: function () {
            var self = this;

            return helper.getView(this.model.id, this.view_name)
                .then(
                    function (data) {
                        self.$el.html(data);
                    }
                );
        }
    });
});
