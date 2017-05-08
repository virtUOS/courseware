define(['assets/js/block_types', './block_types_view', './student_view'], function (block_types, BlockTypesView, StudentView) {
    'use strict';

    return block_types.add({
        name: 'Section',
        views: {
            block_types: BlockTypesView,
            student: StudentView
        }
    });
});
