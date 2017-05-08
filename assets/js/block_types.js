define(["backbone", "./block_type"], function (Backbone, BlockType) {

    'use strict';

    var BlockTypesCollection = Backbone.Collection.extend({
        model: BlockType,

        comparator: 'name',

        findByName: function (name) {
            return this.findWhere({
                name: name
            });
        }
    });

    return new BlockTypesCollection([]);
});
