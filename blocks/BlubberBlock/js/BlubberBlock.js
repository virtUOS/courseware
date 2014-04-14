define(['assets/js/block_type', './student_view', './author_view'], function (BlockType, StudentView, AuthorView) {
    'use strict';

    return new BlockType({
        name: 'BlubberBlock',

        content_block: true,

        views: {
            student: StudentView,
            author: AuthorView
        }
    });
});
