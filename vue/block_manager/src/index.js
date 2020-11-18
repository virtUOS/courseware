import Vue from 'vue';
import store from './store';
import i18n from './i18n';

Vue.config.productionTip = false;

const draggableHack = window.Vue;
delete window.Vue;

import('./BlockManager.vue').then(({ default: BlockManager}) => {
  new Vue({
    el: '#block_mananger_content',
    i18n,
    store,
    render: h => h(BlockManager)
  });

  window.Vue = draggableHack;
});
