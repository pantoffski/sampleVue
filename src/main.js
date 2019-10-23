import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import "bootstrap/scss/bootstrap.scss";

Vue.config.productionTip = false

import panZoom from "vue-panzoom";

// install plugin
Vue.use(panZoom);
new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#app')
