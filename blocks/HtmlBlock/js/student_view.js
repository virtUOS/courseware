define(['backbone', 'assets/js/url'], function (Backbone, urlhelper) {

    'use strict';

    var StudentView = Backbone.View.extend({

        events: {
        },

        initialize: function(options) {
            this.block_id = options.block_id;
        },

        render: function() {
        }
    });

    return StudentView;
});
