import Vue from 'vue';
import BlockManager from './BlockManager.vue';

Vue.config.productionTip = false;

new Vue({
    render: h => h(BlockManager)
}).$mount('#block_mananger_content');
