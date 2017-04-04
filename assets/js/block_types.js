import Backbone from 'backbone'
import BlockType from './block_type'

const BlockTypesCollection = Backbone.Collection.extend({
  model: BlockType,

  comparator: 'name',

  findByName(name) {
    return this.findWhere({ name });
  }
});

export default new BlockTypesCollection([]);
