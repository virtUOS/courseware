define(['assets/js/block', './student_view'], function (Block, StudentView) {

    'use strict';

    return new Block('Section', {
        views:{
            student: StudentView
        }
    });
});
