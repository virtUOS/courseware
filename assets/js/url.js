define(['module', 'argjs'], function (module, Arg) {

    'use strict';

    return {
        base_view: module.config().base_view,

        block_url: function (block_id) {
            return [module.config().blocks_url, "/", block_id].join("");
        },

        getView: function (block_id, view_name) {
            var payload = {
            };

            return $.ajax({
                url: this.block_url(block_id) + "?view=" + view_name,
                accepts: "text/html",
                type: "GET"
            });
        },

        callHandler: function (block_id, handler, data) {

            var payload = {
                data: _.clone(data),
                handler: handler
            };

            return $.ajax({
                url: this.block_url(block_id),
                type: "POST",
                data: JSON.stringify(payload),
                contentType: "application/json",
                dataType: "json"
            });
        },

        switchToView: function (view) {
            var url = Arg.url({ cid: Arg('cid'), view: view });
            window.document.location = url;
        }
    };

});
