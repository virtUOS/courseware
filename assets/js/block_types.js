define(["backbone", "./block_type"], function (Backbone, BlockType) {

    'use strict';

    var BlockTypesCollection = Backbone.Collection.extend({
        model: BlockType,

        comparator: 'name',

        addBlockType: function (args) {
            var type = new BlockType(args);
            this.add(type);
            return type;
        },

        findByName: function (name) {
            return this.findWhere({
                name: name
            });
        }
    });

    return new BlockTypesCollection([]);
});
