define(['backbone'], function (Backbone) {

    var BlockType = function (options) {
        this.name    = options.name;
        this.views   = options.views;
        this.options = options;
    };

    _.extend(BlockType.prototype, Backbone.Events, {

        isContentBlock: function () {
            return !!this.options.content_block;
        },

        createView: function (view_name, options) {
            var self = this,
                klass = this.views[view_name];

            if (!klass) {
                throw ['View class not found: "', this.name, '/', view_name , '"'].join('');
            }

            return _.tap(new klass(options), function (obj) {
                obj.block_type = self;
            });
        }
    });

    return BlockType;
});
