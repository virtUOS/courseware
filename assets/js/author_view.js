define(['./block_view'], function (BlockView) {
    return BlockView.extend({
        view_name: "author",

        switchBack: function () {
            this.trigger("switch", "student");
        }
    });
});
