define(['assets/js/block_type', './student_view'], function (BlockType, StudentView) {

    'use strict';

    return new BlockType({
        name: 'Courseware',
        views: {
            student: StudentView
        }
    });
});
