import Config from './courseware-config'
import Mustache from 'mustache'
import url_helper from './url'

var TEMPLATES = Config.templates || {};

var helpers = {
  i18n() {
    return function (text, render) {
      return render(text);
    };
  },
  plugin_url() {
    return function (text, render) {
      return url_helper.plugin_url(render(text));
    };
  },
  titleize()  {
    return function (text, render) {
      var content = render(text);
      if (content.match(/^\+\+/)) {
        content = '<span class=indented>' + content.substr(2) + '</span>';
      }
      return content;
    };
  }
};

export default function (block_type, template_name, data) {
  var templates = TEMPLATES[block_type] || {};

  if (templates[template_name] == null) {
    throw 'No such template: "' + block_type + '/' + template_name + '"';
  }

  var template_data = { ...helpers, ...data };

  return Mustache.render(templates[template_name], template_data, templates);
}
