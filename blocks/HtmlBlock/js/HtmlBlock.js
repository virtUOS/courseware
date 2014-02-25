define(['assets/js/block', './student_view', './author_view'], function (Block, StudentView, AuthorView) {

    'use strict';

    return new Block('HtmlBlock', {
        views:{
            student: StudentView,
            author: AuthorView
        }
    });
});
