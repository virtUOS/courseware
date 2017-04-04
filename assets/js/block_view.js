import Backbone from 'backbone'
import helper from './url'

export default Backbone.View.extend({

  // filled by block type's createView method
  block_type: null,

  initializeFromDOM() { },

  renderServerSide() {
    return helper
      .getView(this.model.id, this.view_name)
      .then(
        (data) => {
          this.$el.html(data);

          // let the block initialize from the just
          // inserted DOM
          this.initializeFromDOM();

          if (typeof this.postRender === 'function') {
            this.postRender();
          }
        }
      );
  }
});
