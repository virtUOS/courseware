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


    var $els = jQuery('section.block');

    var initializeBlock = function (block_element) {
        var $block = $(block_element),
            $el = $block.find('.block-content'),
            model,
            block;

        model = new BlockModel({
            id:   $block.attr("data-blockid"),
            type: $block.attr("data-blocktype")
        });

        block = block_types.findByName(model.get('type')).createView('student', {el: $el, model: model});


        block.initializeFromDOM();
        block.postRender();

        return block;
    }

    var blocks = _.map($els, initializeBlock);

    jQuery('section.contentbox article').addClass('open');

});
