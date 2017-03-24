({
    /*optimize: "none",*/

    baseUrl: ".",

    mainConfigFile: "assets/js/config.js",

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
        "blocks/AudioBlock/js/AudioBlock",
        "blocks/DownloadBlock/js/DownloadBlock",
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
        "blocks/AudioBlock/js/AudioBlock",
        "blocks/DownloadBlock/js/DownloadBlock",

        "assets/js/main-courseware"
    ]
})
