define(['module', 'argjs'], function (module, Arg) {

    'use strict';

    return {
        block_url: function (block_id, params) {
            var path = [module.config().blocks_url, "/", block_id].join("");
            return Arg.url(path, params || {});
        },

        getView: function (block_id, view_name) {

            return jQuery.ajax({
                url: this.block_url(block_id, { view: view_name }),
                dataType: "html",
                type: "GET"
            });
        },

        putView: function (block_id, data) {

            return jQuery.ajax({
                url: this.block_url(block_id),
                type: "PUT",
                data: JSON.stringify(data),
                contentType: "application/json",
                dataType: "json"
            });
        },

        deleteView: function (block_id) {
            return jQuery.ajax({
                url: this.block_url(block_id),
                type: "DELETE",
                data: "",
                contentType: "application/json",
                dataType: "json"
            });
        },

        callHandler: function (block_id, handler, data) {

            var payload = {
                data: _.clone(data),
                handler: handler
            };

            return jQuery.ajax({
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
