<template>
  <div id="app">
    <router-view v-if="token" />
    <login-panel v-if="!token" />
  </div>
</template>
<script>
import { mapState, mapActions } from "vuex";
import LoginPanel from "./components/LoginPanel.vue";
export default {
  name: "App",
  components: { LoginPanel },
  data() {
    return {};
  },
  methods: {
    ...mapActions("auth", { initToken: "init" })
  },
  computed: {
    ...mapState("auth", ["token"])
  },
  mounted() {
    this.initToken();
  }
};
</script>

<style lang="scss">
#app {
  font-family: "Avenir", Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
}
#nav {
  padding: 30px;
  a {
    font-weight: bold;
    color: #2c3e50;
    &.router-link-exact-active {
      color: #42b983;
    }
  }
}
</style>
