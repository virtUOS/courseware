import Backbone from 'backbone'
import Thread from './thread_model'

var ThreadsCollection = Backbone.Collection.extend({
  model: Thread
});

export default ThreadsCollection;
