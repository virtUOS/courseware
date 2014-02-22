define(['module'], function (module) {

    'use strict';

    return function (type) {
        return module.config().templates[type];
    };
});
