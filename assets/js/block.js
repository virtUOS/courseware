define(['backbone'], function (Backbone) {

    var Block = function Block(name, options) {
        this.name = name;
        this.options = options || (options = {});
    };

    _.extend(Block.prototype, Backbone.Events, {

        createView: function (view_name, options) {
            var klass = this.options.views[view_name];
            if (!klass) {
                throw ['View class not found: "', this.name, '/', view_name , '"'].join('');
            }
            return new klass(options);
        }
    });

    return Block;

});
