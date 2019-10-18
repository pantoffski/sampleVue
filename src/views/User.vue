<template>
  <div class="user container">
    <router-link :to="{name:'userList'}">กลับ</router-link>
    <input type="file" @change="doUpload" ref="inpFile" style="display:none;" />
    <div class="imgContainer">
      <div v-for="(v,index) in imgs" :key="index">
        <img :src="'/upload/'+v.url" @click="zoom(index)" />
        <i v-if="isOwner" class="fa fa-minus-circle fa-2x" @click="delImg(index)"></i>
      </div>
    </div>
    <button v-if="isOwner" class="btn" :class="btnClass" @click.prevent="browse">{{btnTxt}}</button>

    <div class="zoom" v-if="isZoom">
      <hr :style="zoomImgStyle" @click="isZoom=false" />
    </div>
  </div>
</template>

<script>
import { mapState } from "vuex";
export default {
  name: "User",
  props: ["id"],
  data() {
    return {
      isBusy: false,
      user: {},
      imgs: [],
      zoomId: 0,
      isZoom: false,
      sliceSize: 200000,
      stopFlag: false,
      perc: 0
    };
  },
  methods: {
    zoom(idx) {
      this.zoomId = idx;
      this.isZoom = true;
    },
    delImg(idx) {
      if (confirm("ต้องการลบรูปนี้ ?")) {
        this.$http
          .delete(`/api/upload/${this.imgs[idx].id}`)
          .then(r => {})
          .catch(r => {
            alert("เกิดข้อผิดพลาดขึ้น กรุณาลองใหม่อีกครั้ง");
          })
          .finally(r => {
            this.imgs.splice(idx, 1);
          });
      }
    },
    browse() {
      if (this.isBusy) {
        if (confirm("ยกเลิกการเพิ่มรูป")) {
          this.stopFlag = true;
        }
      } else {
        this.$refs.inpFile.click();
      }
    },

    fetch() {
      this.$http
        .get(`/api/user/${this.id}`)
        .then(r => {
          this.user = r.data.user;
          this.imgs = r.data.imgs;
        })
        .catch(e => {
          alert(e.response.data || "เกิดข้อผิดพลาดขึ้น กรุณาลองอีกครั้ง");
        });
    },
    doUpload() {
      var _this = this;
      if (this.isBusy) return;
      if (!this.$refs.inpFile.files[0]) return;
      var inp = this.$refs.inpFile.files[0];
      var url =
        this.uuid() +
        "." +
        inp.name
          .split(".")
          .pop()
          .toLowerCase();
      if (!inp.name.match(/(.png|.jpeg|.jpg)$/i)) return;
      this.isBusy = true;
      this.stopFlag = false;
      var reader = new FileReader();
      reader.onload = async function(file) {
        var data = file.target.result;
        _this.$refs.inpFile.value = "";
        var sliceSize = Math.ceil(_this.sliceSize / 1.34);
        var chunkSize = Math.ceil(data.length / sliceSize),
          chunks = new Array(chunkSize),
          chunkOffset;
        for (var i = 0; i < chunkSize; i++) {
          chunkOffset = i * sliceSize;
          chunks[i] = data.substring(chunkOffset, chunkOffset + sliceSize);
        }
        var isOk = true;
        for (var i = 0; i < chunks.length; i++) {
          _this.perc = Math.floor(((i + 1) / chunks.length) * 100);
          await _this.$http
            .post("/api/upload/chunk", {
              url: url,
              chunkData: window.btoa(chunks[i]),
              chunkId: i,
              chunkTotal: chunks.length
            })
            .catch(e => {
              alert("เกิดข้อผิดพลาดขึ้น กรุณาลองใหม่อีกครั้ง");
              i = chunks.length;
              isOk = false;
            });
          if (_this.stopFlag) {
            i = chunks.length;
            isOk = false;
          }
        }
        if (isOk) {
          await _this.$http
            .post(`/api/upload/move`, {
              to: `${_this.token.id}/${url}`,
              from: url
            })
            .then(r => {
              _this.imgs.push({
                url: `${_this.token.id}/${url}`,
                id: r.data.id
              });
            })
            .catch(e => {})
            .finally(r => {});
        }
        _this.isBusy = false;
      };
      this.$nextTick(_ => {
        reader.readAsBinaryString(inp);
      });
    },
    uuid(a) {
      return a
        ? (a ^ ((Math.random() * 16) >> (a / 4))).toString(16)
        : ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, this.uuid);
    }
  },
  computed: {
    ...mapState("auth", ["token"]),
    isOwner() {
      return this.id == this.token.id;
    },
    zoomImgStyle() {
      if (!this.imgs[this.zoomId]) return {};
      return {
        "background-image": `url(/upload/${this.imgs[this.zoomId].url})`
      };
    },
    btnClass() {
      return this.isBusy ? "btn-warning" : "btn-success";
    },
    btnTxt() {
      return this.isBusy ? `กำลัง upload, ${this.perc}%` : "เพิ่มรูป";
    }
  },
  mounted() {
    this.fetch();
  }
};
</script>


<style lang="scss">
.user {
  padding-top: 10px;
  .zoom {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    overflow: hidden;
    z-index: 2001;
    background: rgba(0, 0, 0, 0.68);
    hr {
      position: absolute;
      top: 20px;
      left: 20px;
      bottom: 20px;
      right: 20px;
      border: 0 none;
      margin: 0;
      height: calc(100vh - 40px);
      cursor: pointer;
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center center;
    }
  }
  .imgContainer {
    & > div {
      display: inline-block;
      padding: 5px;
      margin: 15px;
      position: relative;
      &:hover {
        border-radius: 5px;
        border: 1px solid #aaa;
        padding: 4px;
        i {
          top: -21px;
          right: -16px;
        }
      }
      img {
        max-height: 150px;
        display: block;
      }
      input {
        width: 100%;
      }
      i {
        color: red;
        cursor: pointer;
        position: absolute;
        top: -20px;
        right: -15px;
      }
    }
  }
}
</style>