define(['backbone', './url'], function (Backbone, url) {

    'use strict';

    var BlockModel = Backbone.Model.extend({
        urlRoot: function () {
            return url.block_url("");
        },

        revert: function () {
            if (this.hasChanged()) {
                this.set(this.previousAttributes(), {silent : true});
            }
            return this;
        }
    });


    return BlockModel;
});
