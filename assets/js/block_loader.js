define(['module'], function (module) {

    'use strict';

    var TYPES = module.config().block_types;

    return {
        load: function (name, req, onLoad, config) {

            if (config.isBuild) {
                // TODO
                throw "not yet implemented";
            }

            else {
                var modules = _.map(TYPES, function (type) {
                    return ['blocks/', type, "/js/", type].join('');
                });

                req(modules, function () {
                    onLoad(Array.prototype.slice.call(arguments));
                });
            }
        }
    };
});
