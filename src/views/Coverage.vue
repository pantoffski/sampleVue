<template>
  <div style="height:100vh;width:100vw;">
    <div style="height: 10%; overflow: auto;">
      <h3>พื้นที่ให้บริการ</h3>
      <router-link :to="{name:'userList'}">กลับ</router-link>
      {{selectedArea}}
      <span v-if="loading||loading2">Loading...</span>
    </div>
    <l-map :zoom="zoom" @zoomend="zoomend" :center="center" style="height: 90%">
      <!-- <l-tile-layer :url="url" :attribution="attribution" /> -->
      <l-geo-json
        :visible="show&&zoom<9"
        :geojson="geojson"
        :options="options"
        :options-style="styleFunction"
      />
      <l-geo-json
        :visible="show&&zoom>=9"
        :geojson="geojson2"
        :options="options"
        :options-style="styleFunction"
      />
    </l-map>
  </div>
</template>

<script>
//import { latLng } from "leaflet";
import { LMap, LGeoJson } from "vue2-leaflet";
//import { LMap, LTileLayer, LMarker, LGeoJson } from "vue2-leaflet";

export default {
  name: "Example",
  props: ["id", "pCodes"],
  components: {
    LMap,
    LGeoJson
  },
  data() {
    return {
      loading: false,
      loading2: false,
      show: true,
      enableTooltip: true,
      zoom: 6,
      center: [13, 100.219482],
      geojson: null,
      geojson2: null,
      admCodes: {},
      fillColor: "#e4ce7f",
      url: "http://{s}.tile.osm.org/{z}/{x}/{y}.png",
      attribution:
        '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
      //marker: latLng(13.41322, 101.219482)
    };
  },
  watch: {
    selectedArea(n, o) {
      if (JSON.stringify(n) == JSON.stringify(o)) return;
      console.log(
        this.$route.path,
        this.$router.resolve({
          name: "coverageWithParams",
          params: { id: this.id, pCodes: this.selectedArea }
        }).href
      );
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
      if (!this.geojson || !this.geojson2) return [];
      var ret = this.geojson.features
          .filter(v => v.properties.isSelect == 1)
          .map(v => v.properties.ADM1_PCODE),
        partial = this.geojson.features
          .filter(v => v.properties.isSelect == -1)
          .map(v => v.properties.ADM1_PCODE);
      this.geojson2.features.forEach(v => {
        if (
          partial.includes(v.properties.ADM1_PCODE) &&
          v.properties.isSelect == 1
        )
          ret.push(v.properties.ADM2_PCODE);
      });
      return ret.map(v => v.replace("TH", ""));
    },
    options() {
      return {
        onEachFeature: this.onEachFeatureFunction
      };
    },
    styleFunction(v) {
      const fillColor = this.fillColor; // important! need touch fillColor in computed for re-calculate when change fillColor
      return v => {
        //console.log("style func ", v);
        var col =
          v.properties.isSelect == 1
            ? "green"
            : v.properties.isSelect == 0
            ? fillColor
            : "orange";
        return {
          weight: 2,
          color: "#ECEFF1",
          opacity: 1,
          fillColor: col,
          fillOpacity: 1
        };
      };
    },
    onEachFeatureFunction() {
      if (!this.enableTooltip) {
        return () => {};
      }
      var _this = this;
      return (feature, layer) => {
        layer.on({
          click: function() {
            var isDistrict = feature.properties.ADM2_PCODE ? true : false;
            var idx = isDistrict
              ? _this.geojson2.features.findIndex(
                  v => v.properties.ADM2_PCODE == feature.properties.ADM2_PCODE
                )
              : _this.geojson.features.findIndex(
                  v => v.properties.ADM1_PCODE == feature.properties.ADM1_PCODE
                );
            console.log(isDistrict, idx, feature);

            if (idx >= 0) {
              if (isDistrict) _this.toggleDistrict(idx);
              else _this.toggleProvince(idx);
              var tmpColor = _this.fillColor;
              _this.fillColor = _this.fillColor + "fe";
              _this.$nextTick(_ => (_this.fillColor = tmpColor));
            }
          }
        });
        layer.bindTooltip(
          "<div>code:" +
            (feature.properties.ADM2_PCODE || feature.properties.ADM1_PCODE) +
            "</div><div>name: " +
            (feature.properties.ADM2_TH || feature.properties.ADM1_TH) +
            "</div>",
          { permanent: false, sticky: true }
        );
      };
    }
  },
  methods: {
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

      pCodes = pCodes.map(v => (v = `TH${v}`));
      var partial = pCodes.filter(v => v.length > 4);
      pCodes = pCodes.filter(v => v.length == 4);
      console.log("codes", pCodes, partial);

      for (var ADM1_PCODE of pCodes) {
        this.geojson2.features
          .filter(v => v.properties.ADM1_PCODE == ADM1_PCODE)
          .forEach(v => (v.properties.isSelect = 1));
        this.geojson.features
          .filter(v => v.properties.ADM1_PCODE == ADM1_PCODE)
          .forEach(v => (v.properties.isSelect = 1));
      }
      for (var ADM2_PCODE of partial) {
        this.geojson2.features
          .filter(v => v.properties.ADM2_PCODE == ADM2_PCODE)
          .forEach(v => (v.properties.isSelect = 1));
        this.geojson.features
          .filter(v => v.properties.ADM1_PCODE == ADM2_PCODE.substring(0, 4))
          .forEach(v => (v.properties.isSelect = -1));
      }
      var tmpColor = this.fillColor;
      this.fillColor = this.fillColor + "fe";
      this.$nextTick(_ => (this.fillColor = tmpColor));
    },
    toggleDistrict(idx) {
      console.log("toggle district");
      var currVal = this.geojson2.features[idx].properties.isSelect;
      var ADM1_PCODE = this.geojson2.features[idx].properties.ADM1_PCODE;
      var provIdx = this.geojson.features.findIndex(
        v => v.properties.ADM1_PCODE == ADM1_PCODE
      );
      var newVal = currVal == 1 ? 0 : 1;
      this.geojson2.features[idx].properties.isSelect = newVal;
      var vals = [
        ...new Set(
          this.geojson2.features
            .filter(v => v.properties.ADM1_PCODE == ADM1_PCODE)
            .map(v => v.properties.isSelect)
        )
      ];
      this.geojson.features[provIdx].properties.isSelect =
        vals.length == 1 ? vals[0] : -1;
    },
    toggleProvince(idx) {
      var currVal = this.geojson.features[idx].properties.isSelect;
      var ADM1_PCODE = this.geojson.features[idx].properties.ADM1_PCODE;
      var newVal = currVal == 1 ? 0 : 1;
      this.geojson.features[idx].properties.isSelect = newVal;

      this.geojson2.features
        .filter(v => v.properties.ADM1_PCODE == ADM1_PCODE)
        .forEach(v => (v.properties.isSelect = newVal));
    },
    zoomend(e) {
      this.zoom = e.target._zoom;
    }
  },
  created() {
    this.loading = true;
    this.loading2 = true;
    this.$http.get("/province.geojson").then(response => {
      response.data.features.forEach(v => (v.properties.isSelect = 0));
      this.geojson = response.data;
      this.loading = false;
    });
    this.$http.get("/district.geojson").then(response => {
      response.data.features.forEach(v => {
        v.properties.isSelect = 0;
        this.admCodes[v.properties.ADM1_PCODE] =
          (this.admCodes[v.properties.ADM1_PCODE] || 0) + 1;
      });
      this.geojson2 = response.data;
      this.loading2 = false;
      this.initPCodes();
    });
  }
};
</script>