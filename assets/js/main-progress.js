define(['domReady!', 'scrollTo', './url', './block_types', './block_model'], function (domReady, scrollTo, helper, block_types, BlockModel) {

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

    var $el = jQuery('div.block-content'),
        block = block_types.findByName('DiscussionBlock').createView('student', {
            el: $el,
            model: new BlockModel({})
        });

    block.initializeFromDOM();
    block.postRender();
});
