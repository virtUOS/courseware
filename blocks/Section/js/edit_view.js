import $ from 'jquery'
import Backbone from 'backbone'
import templates from 'js/templates'

export default Backbone.View.extend({

  className: 'edit-section',

  events: {
    'submit form': 'submit',
    'click button.cancel': 'cancel'
  },

  deferred: null,

  initialize() {
    this.deferred = new Promise((resolve, reject) => {
      this.resolve = resolve
      this.reject = reject
    });
    $('.ui-tooltip').remove();
    this.listenTo(Backbone, 'modeswitch', this.cancel, this);
    this.render();
  },

  render() {
    var template = templates('Section', 'edit_view', this.model.toJSON());
    this.$el.html(template);

    return this;
  },

  focus() {
    this.$('input').get(0).focus();
  },

  promise() {
    return this.deferred;
  },

  submit(event) {
    event.preventDefault();
    var new_title = this.$('input').val().trim();
    this.model.set('title', new_title);
    this.resolve(this.model);
  },

  cancel() {
    this.reject && this.reject();
  }
});
