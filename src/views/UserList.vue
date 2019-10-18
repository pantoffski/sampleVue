<template>
  <div class="userList">
    <table>
      <tr v-for="(v,index) in users" :key="index">
        <td>{{v.uName}}</td>
        <td>
          <router-link
            tag="button"
            class="btn btn-primary"
            :to="{name:'user',params:{id:v.id}}"
          >จัดการรูป</router-link>
        </td>
      </tr>
    </table>
  </div>
</template>

<script>
export default {
  name: "UserList",
  data() {
    return {
      users: []
    };
  },
  methods: {
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
