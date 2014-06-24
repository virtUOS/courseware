({
    /*optimize: "none",*/

    baseUrl: ".",

    paths: {
        domReady: "assets/js/domReady",
        jquery:   "assets/js/jquery_compat",
        backbone: "assets/js/vendor/backbone/backbone-min",
        argjs:    "assets/js/vendor/arg.js/arg.js.v1.1",
        mustache: "assets/js/vendor/mustache.js-0.8.1/mustache",
        q:        "assets/js/vendor/q.v1/q.min",
        scrollTo: "assets/js/vendor/jquery.scrollTo/jquery.scrollTo.min",
        utils:    "assets/js/utils",
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
    },

    include: [
        "assets/js/vendor/requirejs.v2.1.11/require-min"
    ],

    name: "assets/js/main-courseware",
    out: "assets/js/main-courseware-built.js",

    /* TODO: The following lines have to be dynamic! */

    deps: [
        "blocks/BlubberBlock/js/BlubberBlock",
        "blocks/Courseware/js/Courseware",
        "blocks/HtmlBlock/js/HtmlBlock",
        "blocks/IFrameBlock/js/IFrameBlock",
        "blocks/Section/js/Section",
        "blocks/TestBlock/js/TestBlock",
        "blocks/VideoBlock/js/VideoBlock"
    ],

    /* TODO: The following lines have to be dynamic! */

    insertRequire: [
        "blocks/BlubberBlock/js/BlubberBlock",
        "blocks/Courseware/js/Courseware",
        "blocks/HtmlBlock/js/HtmlBlock",
        "blocks/IFrameBlock/js/IFrameBlock",
        "blocks/Section/js/Section",
        "blocks/TestBlock/js/TestBlock",
        "blocks/VideoBlock/js/VideoBlock",

        "assets/js/main-courseware"
    ]
})
