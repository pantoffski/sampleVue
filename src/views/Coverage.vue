<template>
  <div class="coverage">
    <vue-zoomer v-if="d">
      <map-area
        @clk="cnt++"
        v-for="(v,index) in d.features"
        :key="index"
        :areaInfo="v"
        :bound="bound"
      />
    </vue-zoomer>
    <br />
    {{cnt}} {{bound}}
  </div>
</template>

<script>
import _debounce from "lodash.debounce";
import vueZoomer from "./vue-zoomer.vue";
import mapArea from "./../components/MapArea.vue";
export default {
  name: "Coverage",
  components: { mapArea, vueZoomer },
  data() {
    return {
      d: null,
      cnt: 0
    };
  },
  methods: {
    async fetchJSON() {
      await this.$http
        .get(`/province.geojson`)
        .then(r => {
          this.d = r.data;
        })
        .catch(e => {})
        .finally(r => {});
    }
  },
  computed: {
    features() {
      if (!this.d) return [];
      return this.d.features.slice(0, 2);
    },
    bound() {
      var top, left, bottom, right;
      if (this.d)
        this.d.features.forEach(vvv =>
          vvv.geometry.coordinates.forEach(vv =>
            vv[0].forEach(v => {
              if (top == undefined || v[1] > top) top = v[1];
              if (bottom == undefined || v[1] < bottom) bottom = v[1];
              if (left == undefined || v[0] < left) left = v[0];
              if (right == undefined || v[0] > right) right = v[0];
            })
          )
        );
      return { top, bottom, left, right };
    }
  },
  async mounted() {
    await this.fetchJSON();
    window.addEventListener("resize", this.onWindowResize);
  }
};
</script>

<style lang="scss">
.coverage {
  width: 100vw;
  height: 500px;
  background: #aaa;
}
</style>