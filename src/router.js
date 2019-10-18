import Vue from 'vue';
import Router from 'vue-router';
import userList from './views/UserList.vue';
import user from './views/User.vue';

Vue.use(Router);

export default new Router({
  mode: 'history',
  base: process.env.BASE_URL,
  routes: [
    {
      path: '/',
      name: 'userList',
      component: userList
    },
    {
      path: '/user/:id',
      name: 'user',
      props: true,
      component: user
    }
  ]
})