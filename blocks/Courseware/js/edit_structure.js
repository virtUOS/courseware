import Backbone from 'backbone'
import dateformat from 'dateformat'
import templates from 'js/templates'

export default Backbone.View.extend({

  className: 'edit-structure',

  events: {
    'submit form': 'submit',
    'change input[name="publication_date"]': 'change_publication_date',
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
      title: this.model.get('title')
    };

    // hide publication_date for sections
    if (this.model.get('type').toUpperCase() !== 'SECTION') {
      if (this.model.get('publication_date')) {
        var date = new Date(this.model.get('publication_date') * 1000);
        data.publication_date = dateformat(date, 'yyyy-mm-dd');
      }
      if (this.model.get('withdraw_date')) {
        var date = new Date(this.model.get('withdraw_date') * 1000);
        data.withdraw_date = dateformat(date, 'yyyy-mm-dd');
      }
      data.chapter = true;
    }
    var template = templates('Courseware', 'edit_structure', data);
    this.$el.html(template);

    return this;
  },

  postRender() {
    this.$('input').eq(0).select().focus();
    this.$('input[name="withdraw_date"]').attr('min', this.$('input[name="publication_date"]').val());
  },

  promise() {
    return this.deferred;
  },
  
  change_publication_date(){
    this.$('input[name="withdraw_date"]').attr('min', this.$('input[name="publication_date"]').val());
  },

  submit(event) {
    event.preventDefault();
    var new_title = this.$('input').val().trim();
    var new_publication_date = Math.floor(Date.parse(this.$('input[name="publication_date"]').val()) / 1000);
    var new_withdraw_date = Math.floor(Date.parse(this.$('input[name="withdraw_date"]').val()) / 1000);

    if (new_title === '') {
      return;
    }

    if (new_publication_date >= new_withdraw_date) {
        return;
    }

    this.model.set({
      title: new_title,
      publication_date: new_publication_date,
      withdraw_date: new_withdraw_date
    });
    this.resolve(this.model);
  },

  cancel() {
    this.reject && this.reject();
  }
});
