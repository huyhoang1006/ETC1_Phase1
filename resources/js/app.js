require('./bootstrap');
import Vue from 'vue'

Vue.component(
    'devices',
    require('./components/DeviceList.vue').default
);
const app = new Vue({
    el: '#mainContent'
});
