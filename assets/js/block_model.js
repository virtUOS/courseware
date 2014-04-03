define(['backbone', './url'], function (Backbone, url) {

    'use strict';

    var BlockModel = Backbone.Model.extend({
        /*
        urlRoot: function () {
            return url.block_url("");
        }
         */
    });


    return BlockModel;
});
