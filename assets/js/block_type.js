import Backbone from 'backbone'

export default Backbone.Model.extend({
  idAttribute: 'name',

  initialize(options) { },

  createView(view_name, options) {
    var klass = this.get('views')[view_name];

    if (!klass) {
      throw [ 'View class not found: "', this.get('name'), '/', view_name , '"' ].join('');
    }

    const view = new klass(options);
    view.block_type = this

    return view
  }
});
