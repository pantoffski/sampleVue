<template>
  <div class="userList container">
    <table class="table table-striped table-hover mt-3">
      <thead>
        <th>ชื่อ user</th>
        <th>จัดการ</th>
        <th>พื้นที่ให้บริการ</th>
      </thead>
      <tbody>
        <tr v-for="(v,index) in users" :key="index">
          <td>{{v.uName}}</td>
          <td>
            <router-link
              tag="button"
              class="btn btn-primary"
              :to="{name:'user',params:{id:v.id}}"
            >จัดการรูป</router-link>
          </td>
          <td>
            <router-link
              tag="button"
              class="btn btn-info"
              :to="{name:'coverage',params:{id:v.id}}"
            >จัดการ</router-link>
          </td>
        </tr>
      </tbody>
    </table>
    <button class="btn btn-danger" @click="logout">logout</button>
  </div>
</template>

<script>
import { mapMutations } from "vuex";
export default {
  name: "UserList",
  data() {
    return {
      users: []
    };
  },
  methods: {
    ...mapMutations("auth", ["logout"]),
    fetch() {
      this.$http
        .get(`/api/user`)
        .then(r => {
          this.users = r.data;
        })
        .catch(e => {
          alert(e.response.data || "เกิดข้อผิดพลาดขึ้น กรุณาลองอีกครั้ง");
        });
    }
  },
  mounted() {
    this.fetch();
  }
};
</script>
