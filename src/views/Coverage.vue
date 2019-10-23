<template>
  <div class="coverage">
    <!-- <panZoom selector="#mapDiv" v-if="d&&d.features" @transform="zoomEnd" :options="{minZoom: 0.5, maxZoom: 15}"> -->
      <div id='mapDiv'  v-if="d&&d.features">
      <svg height="600px" width="100vw" id="mapContainer">
        <g id="map" style='transform:translateX(-200px) scale(1.4)'>
          <map-area v-for="(v,index) in d.features" :key="index" :areaInfo="v" />
        </g>
      </svg>
      </div>
    <!-- </panZoom> -->
  </div>
</template>

<script>
import mapArea from "./../components/MapArea.vue";
export default {
  name: "Coverage",
  components: { mapArea },
  data() {
    return {
      d: {},
      scale: 1
    };
  },
  methods: {
    zoomEnd(e) {
      var _ = e.getTransform();
      this.scale = _.scale;
    },
    fetchJSON() {
      this.$http
        .get(`/province.geojson`)
        .then(r => {
          this.d = r.data;
        })
        .catch(e => {})
        .finally(r => {});
    },initGesture(){
//       node.addEventListener('gestureend', function(e) {
//     if (e.scale < 1.0) {
//         // User moved fingers closer together
//     } else if (e.scale > 1.0) {
//         // User moved fingers further apart
//     }
// }, false);
    }
  },
  computed: {
    features() {
      if (!this.d) return [];
      return this.d.features.slice(0, 2);
    }
  },
  mounted() {
    this.fetchJSON();
  }
};
</script>

<style lang="scss">
.coverage {
  #mapContainer {
    background: #aaa;
  }
}
</style>