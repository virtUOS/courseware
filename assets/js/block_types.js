define([], function (loader) {

    'use strict';

    return {
        types: {
        },

        reset: function (types) {
            this.types = types;
        },

        get: function (name) {
            return this.types[name];
        }
    };
});
