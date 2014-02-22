define(['backbone', './url', './block_model'], function (Backbone, url, BlockModel) {

    'use strict';

    var BlockCollection = Backbone.Collection.extend({

        model: BlockModel,

        url: function () {
            return url.block_url("");
        }
    });


    return BlockCollection;
});
