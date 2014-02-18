define(['module'], function (module) {

    'use strict';

    return {
        load: function (name, req, onLoad, config) {

            if (config.isBuild) {
                // TODO
                throw "not yet implemented";
            }

            else {
                var url = ['blocks', name, "js", name].join('/');

                req([req.toUrl(url)], function (views) {
                    onLoad(views);
                });
            }
        }
    };
});
