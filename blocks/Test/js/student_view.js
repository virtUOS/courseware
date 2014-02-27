define(['assets/js/block_view'],
       function (BlockView) {

    'use strict';

    var StudentView = BlockView.extend({

        // TODO: put this into the super 'class'
        view_name: "student",

        events: {
        },

        initialize: function(options) {
        },

        render: function() {
            return this;
        }
    });

    return StudentView;
});
