define(['assets/js/block_type', './student_view'],
       function (BlockType, StudentView) {

    'use strict';

    return new BlockType({
        name: 'TestBlock',

        content_block: true,

        views:{
            student: StudentView
        }
    });
});
