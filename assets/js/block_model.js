import Backbone from 'backbone'
import url from './url'

export default Backbone.Model.extend({
  urlRoot() {
    return url.block_url('');
  },

  revert() {
    if (this.hasChanged()) {
      this.set(this.previousAttributes(), { silent : true });
    }
    return this;
  }
});
