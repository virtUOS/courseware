define(['module', 'argjs'], function (module, Arg) {

    'use strict';

    return {

        // URL generation

        block_url: function (block_id, params) {
            var path = [module.config().blocks_url, "/", block_id].join("");
            return Arg.url(path, params || {});
        },

        courseware_url: module.config().courseware_url,

        plugin_url: function (path) {
            path = path || "";
            var params = _.extend({ cid: Arg("cid") }, Arg.parse(path));
            return Arg.url(module.config().plugin_url + path, params || {});
        },

        reload: function () {
            window.location.reload(true);
        },

        navigateTo: function (id, hash) {

            var params = Arg.all();
            params.selected = id;

            if (typeof hash === "undefined" || hash === null) {
                hash = window.location.hash;
            }
            if (hash[0] === "#") {
                hash = hash.substr(1);
            }
            var oldLocation = document.location.pathname + document.location.search + document.location.hash;
            var newLocation = Arg.url(Arg.url(), params, hash);

            if (oldLocation !== newLocation) {
                document.location = newLocation;
            } else {
                this.reload();
            }
        },


        getView: function (block_id, view_name) {

            return jQuery.ajax({
                url: this.block_url(block_id, { view: view_name }),
                dataType: "html",
                type: "GET"
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
