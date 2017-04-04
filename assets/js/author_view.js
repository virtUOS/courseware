import BlockView from './block_view'
export default BlockView.extend({
  view_name: 'author',

  switchBack() {
    this.trigger('switch', 'student');
  }
});
