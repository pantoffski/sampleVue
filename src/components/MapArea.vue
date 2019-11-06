<template>
  <g @click="clkHandle" class="mapArea" cursor="pointer" >
    <!-- <g> -->
    <path :foo='areaInfo.properties.ADM1_PCODE' :id="id" :class="isSelected?'y':''" :d="d" stroke="gray" stroke-width="0.01" />
    <!-- </g> -->
  </g>
</template>

<script>
export default {
  name: "MapArea",
  props: ["areaInfo", "bound"],
  data() {
    return {
      isSelected: false,
      id: `path${Math.floor(Math.random() * 100000000)}`,
      dd: "M150 0 L75 200 L225 200 Z"
    };
  },
  methods: {
    clkHandle() {
      this.isSelected = !this.isSelected;
      this.$el.blur();
      this.$emit("clk");
    }
  },
  computed: {
    d() {
      if (!this.areaInfo.geometry) return;
      return this.areaInfo.geometry.coordinates
        .map(
          vv =>
            "M" +
            vv[0]
              .map(v => (v[0]-this.bound.left)+ " " +( this.bound.top-v[1]))
              .join("L") +
            "Z"
        )
        .join(" ");
    }
  }
};
</script>
<style lang="scss">
.mapArea {
 // pointer-events: none;
  path {
    fill: rgb(102, 158, 56);
    &.y {
      fill: green;
    }
    &:hover {
      fill: yellow;
    }
  }
}
</style>
