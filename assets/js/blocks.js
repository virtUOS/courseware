define(['module', 'require'], function (module, require) {

    'use strict';

    return function (type, callback) {
        var url = ['blocks', type, "js", type].join('/');
        require([require.toUrl(url)], function (views) {
            callback(views);
        });
    };


    /*

    var block_types = {},
        readyCalls = [],
        runCallbacks = function () {
            _.each(readyCalls, function (callback) {
                callback(block_types);
            });
        };


    var promise = $.when.apply($,
        _.map(
            module.config().block_types,
            function (type) {
                var deferred = jQuery.Deferred(),
                    url = ['blocks', type, "js", type].join('/');

                require([require.toUrl(url)], function (views) {
                    block_types[type] = views;
                    deferred.resolve(views);
                });

                return deferred.promise();
            }
        )
    );
    promise.then(runCallbacks, function () { throw new "not yet implemented"; });


    var blockLoader = function (callback) {

        console.log ("in blocks", callback);

        if (promise.state() !== "pending") {
            callback(block_types);
        } else {
            readyCalls.push(callback);
        }

        return blockLoader;
    };


    blockLoader.load = function (name, req, onLoad, config) {

        if (config.isBuild) {
            // onLoad(null);
            throw "Not yet implemented";

        } else {
            blockLoader(onLoad);
        }
    };


    return blockLoader;

    */
});
