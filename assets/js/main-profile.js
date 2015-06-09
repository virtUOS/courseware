define(['domReady!', 'scrollTo', './url', './block_types', './block_model'], function (domReady, scrollTo, helper, block_types, BlockModel) {

    var $el = jQuery('section.contentbox .DiscussionBlock');


    _.map($el, function (el) {

        var $el = jQuery(el),
            block = block_types.findByName('DiscussionBlock').createView('student', {
                el: $el,
                model: new BlockModel({})
            });

        block.initializeFromDOM();
        block.postRender();
    });
});
