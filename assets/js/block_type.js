define(['backbone'], function (Backbone) {

    var BlockType = Backbone.Model.extend({

        idAttribute: 'name',

        initialize: function (options) {
        },

        isContentBlock: function () {
            return !!this.get('content_block');
        },

        createView: function (view_name, options) {
            var self = this,
                klass = this.get('views')[view_name];

            if (!klass) {
                throw ['View class not found: "', this.get('name'), '/', view_name , '"'].join('');
            }

            return _.tap(new klass(options), function (obj) {
                obj.block_type = self;
            });
        }
    });

    return BlockType;
});
