define(['q', 'backbone', './url'], function (Q, Backbone, url) {

    'use strict';

    return Backbone.Model.extend({
        urlRoot: function () {
            return url.block_url("");
        },

        revert: function () {
            if (this.hasChanged()) {
                this.set(this.previousAttributes(), {silent : true});
            }
            return this;
        },

        sync: function() {
            var result = Backbone.sync.apply(this, arguments);
            if (Q.isPromiseAlike(result)) {
                return Q(result);
            }
            return result;
        }
    });
});
