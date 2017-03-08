define(['assets/js/block_types', './student_view', './author_view'], function (block_types, StudentView, AuthorView) {

    'use strict';

    return block_types.add({
        name: 'DownloadBlock',

        content_block: true,

        views: {
            student: StudentView,
            author: AuthorView
        }
    });
});
