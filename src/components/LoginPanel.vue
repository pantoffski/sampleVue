<template>
<div class='loginPanel'>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">Login</div>
          <div class="card-body">
            <form action method>
              <div class="form-group row">
                <label
                  for="email_address"
                  class="col-md-4 col-form-label text-md-right"
                >E-Mail Address</label>
                <div class="col-md-6">
                  <input
                    type="text"
                    id="email_address"
                    class="form-control"
                    name="email-address"
                    required
                    autofocus
                  />
                </div>
              </div>

              <div class="form-group row">
                <label for="password" class="col-md-4 col-form-label text-md-right">Password</label>
                <div class="col-md-6">
                  <input
                    type="password"
                    id="password"
                    class="form-control"
                    name="password"
                    required
                  />
                </div>
              </div>

              <div class="form-group row">
                <div class="col-md-6 offset-md-4">
                  <div class="checkbox">
                    <label>
                      <input type="checkbox" name="remember" /> Remember Me
                    </label>
                  </div>
                </div>
              </div>

              <div class="col-md-6 offset-md-4">
                <button type="submit" class="btn btn-primary">Register</button>
                <a href="#" class="btn btn-link">Forgot Your Password?</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div></div>
  </div>
</template>

<script>
import { mapState, mapMutations, mapActions } from "vuex";
export default {
  name: "LoginPanel",
  data() {
    return {
      user: "",
      pass: "",
      isBusy: false
    };
  },
  methods: {
    async doLogin() {
      if (this.isBusy) return;
      this.isBusy = true;
      var res = await this.login({ user: this.user, pass: this.pass });
      this.isBusy = false;
      if (res !== true) alert(res || "เกิดข้อผิดพลาดขึ้น กรุณาลองอีกครั้ง");
    },
    ...mapActions("auth", ["login"])
  }
};
</script>

<style lang="scss">
.loginPanel{
  display: flex;
  align-items: center;
  height:100vh;
}
</style>