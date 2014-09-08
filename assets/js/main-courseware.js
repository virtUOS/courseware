define(['domReady!', 'scrollTo', 'backbone', 'assets/js/url', 'assets/js/block_types', 'assets/js/block_model'], function (domReady, scrollTo, Backbone, helper, block_types, BlockModel) {

    function logError(error) {
        if (console) {
            console.log(error);
        }
    }

    window.onerror  = function (message, file, line) {
        logError(file + ':' + line + '\n\n' + message);
    };

    Backbone.history.start({
        push_state: true,
        silent: true,
        root: helper.courseware_url
    });

    jQuery(document).ready(function () {
        var $el = jQuery("#courseware");
        var model = new BlockModel({
            id: $el.attr("data-blockid"),
            type: "Courseware"
        });
        block_types.findByName("Courseware").createView("student", {
            el: $el,
            model: model
        });
    });
});
