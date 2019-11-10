<template>
  <div style="height:100vh;width:100vw;">
    <div style="height: 10%; overflow: auto;">
      <h3>พื้นที่ให้บริการ</h3>
      <router-link :to="{name:'userList'}">กลับ</router-link>
      {{selectedArea}}
      <span v-if="!isLoaded">Loading...</span>
    </div>
    <div id="map" style="height:90%;position:relative;"></div>
  </div>
</template>

<script>
//var L= require( "leaflet");

import L from "leaflet";
//import { LMap, LTileLayer, LMarker, LGeoJson } from "vue2-leaflet";

export default {
  name: "Coverage",
  props: ["id", "pCodes"],
  components: {},
  data() {
    return {
      isLoaded: false,
      zoom: 6,
      showDistrictAtZoomLvl: 9,
      map: null,
      provLayer: null,
      provGridLayer: null,
      distLayer: null,
      center: [13, 100.219482],
      geojson: null,
      geojson2: null,
      fillColor: "burlywood",
      provDist: {},
      distProv: {},
      selectedProv: this.pCodes
        ? JSON.parse(
            JSON.stringify(
              typeof this.pCodes == "string"
                ? this.pCodes.split("/")
                : this.pCodes
            )
          )
            .filter(v => v.length == 2)
            .reduce((acc, key) => ({ ...acc, [key]: 1 }), {})
        : [],
      selectedDist: this.pCodes
        ? JSON.parse(
            JSON.stringify(
              typeof this.pCodes == "string"
                ? this.pCodes.split("/")
                : this.pCodes
            )
          )
            .filter(v => v.length == 4)
            .reduce((acc, key) => ({ ...acc, [key]: 1 }), {})
        : {}
    };
  },
  watch: {
    selectedArea(n, o) {
      if (JSON.stringify(n) == JSON.stringify(o)) return;
      if (
        this.$route.path ==
        this.$router.resolve({
          name: "coverageWithParams",
          params: { id: this.id, pCodes: this.selectedArea }
        }).href
      )
        return;
      this.$http
        .post(`/api/user/coverage`, {
          id: this.id,
          area: this.selectedArea.join("/")
        })
        .then(r => {
          if (this.selectedArea.length == 0)
            this.$router.push({ name: "coverage", params: { id: this.id } });
          else
            this.$router.push({
              name: "coverageWithParams",
              params: { id: this.id, pCodes: this.selectedArea }
            });
        })
        .catch(e => {
          alert(e.response.data || "เกิดข้อผิดพลาดขึ้น กรุณาลองอีกครั้ง");
        });
    }
  },
  computed: {
    selectedArea() {
      if (!this.isLoaded) return [];
      var ret = [];
      for (var k in this.selectedProv) {
        if (this.selectedProv[k] == 1) ret.push(k);
      }
      for (var k in this.selectedProv) {
        if (this.selectedProv[k] == -1) {
          this.provDist[k].forEach(v => {
            if (this.selectedDist[v]) ret.push(v);
          });
        }
      }
      return ret;
    }
  },
  methods: {
    layerClick(e) {
      var v = e.target.feature.properties;
      var fillColor = "burlywood";
      if (v.ADM2_PCODE) {
        if (!this.selectedDist[v.ADM2_PCODE]) fillColor = "green";
      } else {
        if (this.selectedProv[v.ADM1_PCODE] != 1) fillColor = "green";
      }
      e.target.setStyle({
        weight: 2,
        color: "whitesmoke",
        opacity: 1,
        fillColor,
        fillOpacity: 1
      });

      this.toggle(v.ADM2_PCODE || v.ADM1_PCODE);
    },
    onEachFeatureFunc(feature, layer) {
      layer.on({ click: this.layerClick });
      var isPermanent = feature.properties.w > 0.1875 * Math.pow(2, this.zoom);
      layer.bindTooltip(
        feature.properties.ADM2_TH || feature.properties.ADM1_TH,
        isPermanent
          ? {
              permanent: true,
              direction: "center",
              className: "provNamePermanent"
            }
          : { direction: "auto" }
      );
    },
    styleFunc(v) {
      var fillColor = "burlywood";
      if (v.properties.ADM2_PCODE) {
        if (this.selectedDist[v.properties.ADM2_PCODE]) fillColor = "green";
      } else {
        if (this.selectedProv[v.properties.ADM1_PCODE])
          fillColor =
            this.selectedProv[v.properties.ADM1_PCODE] == 1
              ? "green"
              : "orange";
      }

      return {
        weight: 2,
        color: "whitesmoke",
        opacity: 1,
        fillColor,
        fillOpacity: 1
      };
    },
    zoomend() {
      if (!this.map) return;
      var _this = this;
      var newZoom = this.map.getZoom();
      if (
        newZoom >= this.showDistrictAtZoomLvl &&
        this.zoom < this.showDistrictAtZoomLvl
      ) {
        this.provLayer.remove();
        this.distLayer.addTo(this.map);
        this.provGridLayer.addTo(this.map);
      }
      if (
        newZoom < this.showDistrictAtZoomLvl &&
        this.zoom >= this.showDistrictAtZoomLvl
      ) {
        this.distLayer.remove();
        this.provGridLayer.remove();
        this.provLayer.addTo(this.map);
      }
      this.map.getPane("label").classList.remove(`zoom${this.zoom}`);
      this.map.getPane("label").classList.add(`zoom${newZoom}`);
      this.zoom = newZoom;

      console.log("zoomend", this.map.getZoom());
    },
    initPCodes() {
      var pCodes = this.pCodes
        ? JSON.parse(
            JSON.stringify(
              typeof this.pCodes == "string"
                ? this.pCodes.split("/")
                : this.pCodes
            )
          )
        : [];
      var selectedDist = pCodes
        .filter(v => v.length == 4)
        .reduce((acc, key) => ({ ...acc, [key]: 1 }), {});
      var selectedProv = pCodes
        .filter(v => v.length == 4)
        .map(v => v.substring(0, 2))
        .reduce((acc, key) => ({ ...acc, [key]: -1 }), {});
      pCodes.filter(v => v.length == 2).map(v => (selectedProv[v] = 1));
      this.geojson2.features.forEach(v => {
        //count district per prov
        if (!this.provDist[v.properties.ADM1_PCODE])
          this.provDist[v.properties.ADM1_PCODE] = [];
        this.provDist[v.properties.ADM1_PCODE].push(v.properties.ADM2_PCODE);
        this.distProv[v.properties.ADM2_PCODE] = v.properties.ADM1_PCODE;
      });
      for (var k in selectedProv) {
        if (selectedProv[k] == 1)
          this.provDist[k].forEach(v => (selectedDist[v] = 1));
      }
      this.selectedProv = selectedProv;
      this.selectedDist = selectedDist;

      var map = L.map("map", { minZoom: 6, maxZoom: 13 });
      map.createPane("dist");
      map.getPane("dist").style.zIndex = 550;
      map.createPane("prov");
      map.getPane("prov").style.zIndex = 551;
      map.createPane("label");
      map.getPane("label").style.zIndex = 552;
      // permanent province label
      this.geojson.features.forEach(v => {
        var zLevel =
          10 - Math.floor(Math.log(v.properties.w * 10) / Math.log(2));
        var provNameClass = `z${
          zLevel < this.showDistrictAtZoomLvl
            ? zLevel
            : this.showDistrictAtZoomLvl - 1
        }`;
        var m = L.circleMarker(v.properties.cm, {
          pane: "label",
          interactive: false,
          opacity: 0,
          fillColor: "transparent"
        });
        m.addTo(map);
        m.bindTooltip(v.properties.ADM1_TH, {
          pane: "label",
          permanent: true,
          direction: "center",
          className: "provNamePermanent " + provNameClass
        });
      });
      // // permanent district label
      // this.geojson2.features.forEach(v => {
      //   var zLevel =
      //     13 - Math.floor(Math.log(v.properties.w * 30) / Math.log(2));
      //   console.log(zLevel);

      //   var provNameClass = `z${
      //     zLevel < this.showDistrictAtZoomLvl
      //       ? this.showDistrictAtZoomLvl
      //       : zLevel
      //   }`;
      //   var m = L.circleMarker(v.properties.cm, {
      //     pane: "label",
      //     interactive: false,
      //     opacity: 0,
      //     fillColor: "transparent"
      //   });
      //   m.addTo(map);
      //   m.bindTooltip(v.properties.ADM2_TH, {
      //     pane: "label",
      //     permanent: true,
      //     direction: "center",
      //     className: "provNamePermanent " + provNameClass
      //   });
      // });

      map.on("zoomend", this.zoomend);
      map.setView(this.center, this.zoom);
      this.map = map;

      this.provGridLayer = L.geoJSON(this.geojson, {
        pane: "prov",
        style: {
          weight: 3,
          color: "#c05c24",
          opacity: 1,
          fillColor: "transparent",
          fillOpacity: 1,
          interactive: false
        }
      });
      this.distLayer = L.geoJSON(this.geojson2, {
        pane: "dist",
        style: this.styleFunc,
        onEachFeature: this.onEachFeatureFunc
      });
      this.provLayer = L.geoJSON(this.geojson, {
        style: this.styleFunc,
        onEachFeature: this.onEachFeatureFunc
      });
      this.provLayer.addTo(this.map);
      this.zoomend();
      this.isLoaded = true;
    },
    toggle(id) {
      id += "";
      console.log("toggle ", id);
      var currVal;
      var selectedProv = JSON.parse(JSON.stringify(this.selectedProv));
      var selectedDist = JSON.parse(JSON.stringify(this.selectedDist));
      var _this = this;
      if (id.length == 4) {
        //district
        currVal = selectedDist[id];
        if (currVal == 1) delete selectedDist[id];
        else selectedDist[id] = 1;
        // resolve province's status
        var provCode = id.substring(0, 2);
        var districtInSameProv = Object.keys(selectedDist).filter(
          v => v.substring(0, 2) == provCode
        ).length;
        selectedProv[provCode] = -1;
        if (districtInSameProv == 0) delete selectedProv[provCode];
        if (districtInSameProv == this.provDist[provCode].length)
          selectedProv[provCode] = 1;
      } else {
        //province
        currVal = selectedProv[id];
        if (currVal == 1) {
          delete selectedProv[id];
          this.provDist[id].forEach(v => delete selectedDist[v]);
        } else {
          selectedProv[id] = 1;
          this.provDist[id].forEach(v => (selectedDist[v] = 1));
        }
      }
      this.selectedProv = selectedProv;
      this.selectedDist = selectedDist;

      // toggle layer's color side effect
      if (id.length == 4) {
        //district
        this.provLayer.eachLayer(function(layer) {
          if (layer.feature.properties.ADM1_PCODE == provCode) {
            layer.setStyle(_this.styleFunc(layer.feature));
          }
        });
      } else {
        //province
        this.distLayer.eachLayer(function(layer) {
          if (layer.feature.properties.ADM1_PCODE == id) {
            layer.setStyle(_this.styleFunc(layer.feature));
          }
        });
      }
    }
  },
  async created() {
    await this.$http.get("/province.geojson").then(response => {
      this.geojson = response.data;
    });
    await this.$http.get("/district.geojson").then(response => {
      this.geojson2 = response.data;
    });
    this.initPCodes();
  },
  beforeDestroy() {
    this.map.off();
    this.map.remove();
  }
};
</script>
<style lang="scss">
.leaflet-label-pane {
  .leaflet-tooltip {
    color: transparent;
  }
  &.zoom5 {
    .z5 {
      color: black;
    }
  }
  &.zoom6 {
    .z5,
    .z6 {
      color: black;
    }
  }
  &.zoom7 {
    .z5,
    .z6,
    .z7 {
      color: black;
    }
  }
  &.zoom8 {
    .z5,
    .z6,
    .z7,
    .z8 {
      color: black;
    }
  }
  &.zoom9 {
    .z9 {
      color: black;
    }
  }
  &.zoom10 {
    .z9,
    .z10 {
      color: black;
    }
  }
  &.zoom11 {
    .z9,
    .z10,
    .z11 {
      color: black;
    }
  }
  &.zoom12 {
    .z9,
    .z10,
    .z11,
    .z12 {
      color: black;
    }
  }
  &.zoom13 {
    .z9,
    .z10,
    .z11,
    .z12,
    .z13 {
      color: black;
    }
  }
}
.leaflet-tooltip.provNamePermanent {
  background: none;
  border: none;
  box-shadow: none;
}
</style>