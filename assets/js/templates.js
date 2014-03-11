define(['module', 'mustache'], function (module, Mustache) {

    'use strict';

    var TEMPLATES = module.config().templates || {};

    return function (block_type, template_name, data) {
        var templates = TEMPLATES[block_type] || {};

        if (templates[template_name] == null) {
            throw 'No such template: "' + block_type + '/' + template_name + '"';
        }

        return Mustache.render(templates[template_name], data, templates);
    };
});
