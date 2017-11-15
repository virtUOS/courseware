import Backbone from 'backbone'
import dateformat from 'dateformat'
import templates from 'js/templates'

export default Backbone.View.extend({

  className: 'edit-structure',

  events: {
    'submit form': 'submit',
    'click button.cancel': 'cancel'
  },

  deferred: null,

  initialize() {
    this.deferred = new Promise((resolve, reject) => {
      this.resolve = resolve
      this.reject = reject
    })
    this.listenTo(Backbone, 'modeswitch', this.cancel, this);
    this.render();
  },

  render() {
    var data = {
      title: this.model.get('title'),
      visible_since_title: this.model.get('visible_since_title')
    };

    // hide publication_date for sections
    if (this.model.get('type') !== 'Section') {
      if (this.model.get('publication_date')) {
        var date = new Date(this.model.get('publication_date') * 1000);
        data.publication_date = dateformat(date, 'yyyy-mm-dd');
      }
      data.chapter = true;
    }
    var template = templates('Courseware', 'edit_structure', data);
    this.$el.html(template);

    return this;
  },

  postRender() {
    if (typeof window.Modernizr === 'undefined' || !window.Modernizr.inputtypes.date) {
      this.$('input[type=date]').datepicker({
        dateFormat: Backbone.$.datepicker.W3C
      });
    }
    this.$('input').eq(0).select().focus();
  },

  promise() {
    return this.deferred;
  },

  submit(event) {
    event.preventDefault();
    var new_title = this.$('input').val().trim();
    var new_publication_date = Math.floor(Date.parse(this.$('input[type=date]').val()) / 1000);

    if (new_title === '') {
      return;
    }

    this.model.set({
      title: new_title,
      publication_date: new_publication_date
    });
    this.resolve(this.model);
  },

  cancel() {
    this.reject && this.reject();
  }
});
