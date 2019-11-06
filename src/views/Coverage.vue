<template>
  <div style='height:100vh;width:100vw;'>
    <div style="height: 10%; overflow: auto;">
      <h3>GeoJSON</h3>
      <span v-if="loading">Loading...</span>
      <label for="checkbox">GeoJSON Visibility</label>
      <input id="checkbox" v-model="show" type="checkbox" />
      <label for="checkboxTooltip">Enable tooltip</label>
      <input id="checkboxTooltip" v-model="enableTooltip" type="checkbox" />
      <input v-model="fillColor" type="color" />
      <br />
    </div>
    <l-map :zoom="zoom" @zoomend='zoomend' :center="center" style="height: 90%">
      <l-tile-layer :url="url" :attribution="attribution" />
      <l-geo-json
        v-if="show&&zoom<8"
        :geojson="geojson"
        :options="options"
        :options-style="styleFunction"
      />
      <l-geo-json
        v-if="show&&zoom>=8"
        :geojson="geojson2"
        :options="options"
        :options-style="styleFunction"
      />
    </l-map>
  </div>
</template>

<script>
import { latLng } from "leaflet";
import { LMap, LTileLayer, LMarker, LGeoJson } from "vue2-leaflet";

import axios from "axios";

export default {
  name: "Example",
  components: {
    LMap,
    LTileLayer,
    LGeoJson,
    LMarker
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
      fillColor: "#e4ce7f",
      url: "http://{s}.tile.osm.org/{z}/{x}/{y}.png",
      attribution:
        '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
      marker: latLng(13.41322, 101.219482)
    };
  },
  computed: {
    options() {
      return {
        onEachFeature: this.onEachFeatureFunction
      };
    },
    styleFunction() {
      const fillColor = this.fillColor; // important! need touch fillColor in computed for re-calculate when change fillColor
      return () => {
        return {
          weight: 2,
          color: "#ECEFF1",
          opacity: 1,
          fillColor: fillColor,
          fillOpacity: 1
        };
      };
    },
    onEachFeatureFunction() {
      if (!this.enableTooltip) {
        return () => {};
      }
      return (feature, layer) => {
        layer.bindTooltip(
          "<div>code:" +
            feature.properties.code +
            "</div><div>nom: " +
            feature.properties.nom +
            "</div>",
          { permanent: false, sticky: true }
        );
      };
    }
  },methods:{zoomend(e){this.zoom=e.target._zoom;
  },},
  created() {
    this.loading = true;
    this.loading2 = true;
    axios
      .get(
        "./province.geojson"
      )
      .then(response => {
        this.geojson = response.data;
        this.loading = false;
      });
    axios
      .get(
        "./district.geojson"
      )
      .then(response => {
        this.geojson2 = response.data;
        this.loading2 = false;
      });
  }
};
</script>