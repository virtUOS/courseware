import Vue from "vue";
import BlockManager from "./BlockManager.vue";
import i18n from "./i18n";

Vue.config.productionTip = false;

new Vue({
  el: "#block_mananger_content",
  i18n,
  render: h => h(BlockManager)
});
