(function () {
    "use strict";

    requirejs.config({
        paths: {
            domReady:   "assets/js/domReady",
            jquery:     "assets/js/jquery_compat",
            underscore: "assets/js/vendor/underscore/underscore-min",
            backbone:   "assets/js/vendor/backbone/backbone-min",
            argjs:      "assets/js/vendor/arg.js/arg.js.v1.1",
            mustache:   "assets/js/vendor/mustache.js-0.8.1/mustache",
            q:          "assets/js/vendor/q.v1/q.min",
            scrollTo:   "assets/js/vendor/jquery.scrollTo/jquery.scrollTo.min",
            autosize:   "assets/js/vendor/jquery.autosize/autosize.min",
            utils:      "assets/js/utils",
            dateFormat: "assets/js/vendor/date.format/date.format"
        },

        shim: {
            backbone: {
                exports: 'Backbone'
            },
            argjs: {
                exports: 'Arg'
            },
            scrollTo: {
                deps: ['jquery']
            }
        }
    });

    requirejs.onError = function (err) {
        console.log("mein onError", err.requireType, err.requireModules);
        throw err;
    };
}());
