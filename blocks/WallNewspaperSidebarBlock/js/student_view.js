define(['assets/js/student_view', 'backbone'], function (StudentView, Backbone) {

    'use strict';

    var parseJSON = function (block, text) {
        var rawJSON = JSON.parse(text);

        return {
            visible: !!rawJSON.visible,
            wall_newspaper_block_id: parseInt(rawJSON.wall_newspaper_block_id, 10)
        };
    }

    return StudentView.extend({

        store: null,

        initialize: function () {
            this.listenTo(Backbone, 'WallNewspaperBlock:passed', this.onWallNewspaperBlockPassed);
        },

        initializeFromDOM: function () {
            this.store = new Backbone.Model(parseJSON(this, this.$('script')[0].textContent))
        },

        render: function() {
            return this;
        },

        onWallNewspaperBlockPassed: function (wall_newspaper_block_id) {
            if (wall_newspaper_block_id === this.store.get('wall_newspaper_block_id')) {
                this.renderServerSide();
            }
        },

        postRender: function () {
            MathJax.Hub.Queue(["Typeset", MathJax.Hub, this.el]);
        }
    });
});
