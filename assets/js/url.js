define(['module', 'argjs'], function (module, Arg) {

    'use strict';

    return {
        reload: function () {
            window.location.reload(true);
        },

        navigateTo: function (id_or_url, hash) {
            var is_numeric = parseInt(id_or_url, 10) == id_or_url,
                params = Arg.all(),
                url = id_or_url;

            if (is_numeric) {
                params.selected = id_or_url;
                url = Arg.url();
            }
            if (typeof hash === "undefined" || hash === null) {
                hash = window.location.hash;
            }

            if (hash[0] === "#") {
                hash = hash.substr(1);
            }

            var href = Arg.url(url, params, hash);
            if (!href.match(/:\/\//)) {
                href = window.location.protocol + "//"  +  window.location.host + href;
            }

            window.location.href = href;
        },

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
        }
    };

});
