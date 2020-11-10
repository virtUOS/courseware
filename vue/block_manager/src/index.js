import Vue from 'vue';
import BlockManager from './BlockManager.vue';
import store from './store';

Vue.config.productionTip = false;

new Vue({
    el: '#block_mananger_content',
    store,
    render: h => h(BlockManager)
});
