define(['assets/js/block_types', './student_view'], function (block_types, StudentView) {

    'use strict';

    return block_types.add({
        name: 'EvaluationBlock',

        content_block: true,

        views: {
            student: StudentView
        }
    });
});
