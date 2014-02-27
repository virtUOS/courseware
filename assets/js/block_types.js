define(["backbone", "./block_type"], function (Backbone, BlockType) {

    'use strict';

    var BlockTypesCollection = Backbone.Collection.extend({
        model: BlockType
    });

    return new BlockTypesCollection([]);
});
