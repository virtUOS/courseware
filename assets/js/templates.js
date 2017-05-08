define(['module', 'mustache', './url'], function (module, Mustache, url_helper) {

    'use strict';

    var TEMPLATES = module.config().templates || {};

    var helpers = {
        i18n: function () {
            return function(text, render) {
                return render(text);
            };
        },
        plugin_url: function () {
            return function (text, render) {
                return url_helper.plugin_url(render(text));
            };
        },
        titleize: function ()  {
            return function (text, render) {
                var content = render(text);
                if (content.match(/^\+\+/)) {
                    content = "<span class=indented>" + content.substr(2) + "</span>";
                }
                return content;
            };
        }
    };

    return function (block_type, template_name, data) {
        var templates = TEMPLATES[block_type] || {};

        if (templates[template_name] == null) {
            throw 'No such template: "' + block_type + '/' + template_name + '"';
        }

        var template_data = _.extend({}, helpers, data);

        return Mustache.render(templates[template_name], template_data, templates);
    };
});
