<template>
  <div style="height:100vh;width:100vw;">
    <div style="height: 10%; overflow: auto;">
      <h3>พื้นที่ให้บริการ</h3>
      <router-link :to="{name:'userList'}">กลับ</router-link>
      {{selectedArea}}
      <span v-if="!isLoaded">Loading...</span>
    </div>
    <l-map v-if="isLoaded" :zoom="zoom" @zoomend="zoomend" :center="center" style="height: 90%">
      <!-- <l-tile-layer :url="url" :attribution="attribution" /> -->
      <l-geo-json
        :visible="show&&zoom<9"
        :geojson="geojson"
        :options="{
        onEachFeature: onEachFeatureFunc
      }"
        :options-style="styleFunc"
      />
      <l-geo-json
        :visible="show&&zoom>=9"
        :geojson="geojson2"
        :options="{
        onEachFeature: onEachFeatureFunc
      }"
        :options-style="styleFunc"
      />
      <l-geo-json
        :visible="show&&zoom>=9"
        :geojson="geojson"
        :options-style="{weight: 3,color: '#c05c24',opacity: 1,fillColor: 'transparent',fillOpacity: 1,interactive: false}"
      />
    </l-map>
  </div>
</template>

<script>
//import { latLng } from "leaflet";
import { LMap, LGeoJson } from "vue2-leaflet";
//import { LMap, LTileLayer, LMarker, LGeoJson } from "vue2-leaflet";

export default {
  name: "Coverage",
  props: ["id", "pCodes"],
  components: {
    LMap,
    LGeoJson
  },
  data() {
    return {
      isLoaded: false,
      loading: false,
      loading2: false,
      show: true,
      enableTooltip: true,
      zoom: 6,
      center: [13, 100.219482],
      geojson: null,
      geojson2: null,
      fillColor: "burlywood",
      trigger: 0,
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
      return ret.map(v => v.replace("TH", ""));
    }
  },
  methods: {
    layerClick(e) {
      var v = e.target.feature.properties;
      var fillColor = "burlywood";
      if (v.ADM2_PCODE) {
        if (!this.selectedDist[v.ADM2_PCODE]) fillColor = "green";
      } else {
        if (this.selectedProv[v.ADM1_PCODE] == null) fillColor = "green";
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
      layer.bindTooltip(
        "<div>code:" +
          (feature.properties.ADM2_PCODE || feature.properties.ADM1_PCODE) +
          "</div><div>name: " +
          (feature.properties.ADM2_TH || feature.properties.ADM1_TH) +
          "</div>",
        { permanent: false, sticky: true }
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
      this.isLoaded = true;
    },
    toggle(id) {
      id += "";
      console.log("toggle ", id);
      var currVal;
      var selectedProv = JSON.parse(JSON.stringify(this.selectedProv));
      var selectedDist = JSON.parse(JSON.stringify(this.selectedDist));

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
          selectedProv[provCode] == 1;
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
    },
    zoomend(e) {
      this.zoom = e.target._zoom;
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
  }
};
</script>