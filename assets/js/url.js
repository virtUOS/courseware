define(['module'], function (module) {

    'use strict';

    var block_url = function (block_id) {
        return [module.config().blocks_url, "/", block_id].join("");
    };


    return {
        callHandler: function (block_id, handler, data) {

            var payload = {
                data: _.clone(data),
                handler: handler
            };

            return $.ajax({
                url: block_url(block_id),
                type: "POST",
                data: JSON.stringify(payload),
                contentType: "application/json",
                dataType: "json"
            });
        }
    };

});
