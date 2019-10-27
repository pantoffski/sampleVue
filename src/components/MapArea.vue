<template>
  <g @click="clkHandle" class="mapArea" cursor="pointer" pointer-events="all">
    <!-- <g> -->
    <path :id="id" :class="isSelected?'y':''" :d="d" />
    <!-- </g> -->
  </g>
</template>

<script>
export default {
  name: "MapArea",
  props: ["areaInfo"],
  data() {
    return {
      isSelected: false,
      scale: 30,
      id: `path${Math.floor(Math.random() * 1000000)}`,
      dd: "M150 0 L75 200 L225 200 Z"
    };
  },
  methods: {
    clkHandle() {
      this.isSelected = !this.isSelected;
    this.$emit('clk');}
  },
  computed: {
    d() {
      if (!this.areaInfo.geometry) return;
      return this.areaInfo.geometry.coordinates[0]
        .map(
          vv =>
            "M" +
            vv
              .map(
                v => (v[0] - 90) * this.scale + " " + (20 - v[1]) * this.scale
              )
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
