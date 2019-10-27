<template>
  <div class="coverage">
    <!-- <panZoom
      selector="#map"
      v-if="d&&d.features"
      @transform="zoomEnd"
      :options="{minZoom: 0.5, maxZoom: 15}"
    >-->
    <svg
      ref="elm"
      height="300px"
      width="100vw"
      id="mapContainer"
      @mousedown="onMouseDown"
      @mouseup="onMouseUp"
      @mousemove="onMouseMove"
      @mouseout="setPointerPosCenter"
      :style="wrapperStyle"
    >
      <g id="map" ref="map">
        <map-area @clk="cnt++" v-for="(v,index) in d.features" :key="index" :areaInfo="v" />
      </g>
    </svg>
    <!-- </panZoom> -->
    <br />
    {{cnt}} {{pointerPosX}} {{isPointerDown}} {{panLocked}}
    {{translateX}}
  </div>
</template>

<script>
import _debounce from "lodash.debounce";
import mapArea from "./../components/MapArea.vue";
export default {
  name: "Coverage",
  components: { mapArea },
  data() {
    return {
      d: {},
      scale: 1,
      cnt: 0, // Container sizes, used to determin the initial zoomer size.
      // Need to reactive to window resizing.
      containerWidth: 1,
      containerHeight: 1,
      containerLeft: 0,
      containerTop: 0,
      // Store values: Interactions will at last change these values.
      // After rotation or resize, these values will keep still.
      // These three values are all relative to the container size.
      translateX: 0,
      animTranslateX: 0,
      translateY: 0,
      animTranslateY: 0,
      scale: 1,
      minScale: 0.2,
      maxScale: 5,
      animScale: 1,
      // Mouse states
      lastFullWheelTime: 0,
      lastWheelTime: 0,
      lastWheelDirection: "y",
      isPointerDown: false,
      pointerPosX: -1,
      pointerPosY: -1,
      twoFingerInitDist: 0,
      panLocked: false,
      // Others
      raf: null,
      tapDetector: null
    };
  },
  watch: {
    scale(val) {
      if (val !== 1) {
        this.$emit("update:zoomed", true);
        this.panLocked = false;
      }
    },
    resetTrigger: "reset"
  },
  methods: {
    onInteractionEnd: _debounce(function() {
      this.limit();
     /// this.panLocked = this.scale === 1;
      this.$emit("update:zoomed", !this.panLocked);
    }, 100),
    // limit the scale between max and min and the translate within the viewport
    limit() {
      // scale
      if (this.scale < this.minScale) {
        this.scale = this.minScale;
        // FIXME this sometimes will not reset when pinching in
        // this.tryToScale(this.minScale / this.scale)
      } else if (this.scale > this.maxScale) {
        this.tryToScale(this.maxScale / this.scale);
      }
      // translate
      if (this.limitTranslation) {
        let translateLimit = this.calcTranslateLimit();
        if (Math.abs(this.translateX) > translateLimit.x) {
          this.translateX *= translateLimit.x / Math.abs(this.translateX);
        }
        if (Math.abs(this.translateY) > translateLimit.y) {
          this.translateY *= translateLimit.y / Math.abs(this.translateY);
        }
      }
    },
    calcTranslateLimit() {
      if (this.getMarginDirection() === "y") {
        let imageToContainerRatio =
          this.containerWidth / this.aspectRatio / this.containerHeight;
        let translateLimitY = (this.scale * imageToContainerRatio - 1) / 2;
        if (translateLimitY < 0) translateLimitY = 0;
        return {
          x: (this.scale - 1) / 2,
          y: translateLimitY
        };
      } else {
        let imageToContainerRatio =
          (this.containerHeight * this.aspectRatio) / this.containerWidth;
        let translateLimitX = (this.scale * imageToContainerRatio - 1) / 2;
        if (translateLimitX < 0) translateLimitX = 0;
        return {
          x: translateLimitX,
          y: (this.scale - 1) / 2
        };
      }
    },
    getMarginDirection() {
      let containerRatio = this.containerWidth / this.containerHeight;
      return containerRatio > this.aspectRatio ? "x" : "y";
    },
    onDoubleTap(ev) {
      if (this.scale === 1) {
        if (ev.clientX > 0) {
          this.pointerPosX = ev.clientX;
          this.pointerPosY = ev.clientY;
        }
        this.tryToScale(Math.min(3, this.maxScale));
      } else {
        this.reset();
      }
      this.onInteractionEnd();
    },
    // reactive
    onWindowResize() {
      let styles = window.getComputedStyle(this.$el);
      this.containerWidth = parseFloat(styles.width);
      this.containerHeight = parseFloat(styles.height);
      this.setPointerPosCenter();
      this.limit();
    },
    setPointerPosCenter() {
      this.pointerPosX = this.containerLeft + this.containerWidth / 2;
      this.pointerPosY = this.containerTop + this.containerHeight / 2;
    },
    // pan
    onPointerMove(newMousePosX, newMousePosY) {
      if (this.isPointerDown) {
        let pixelDeltaX = newMousePosX - this.pointerPosX;
        let pixelDeltaY = newMousePosY - this.pointerPosY;
        console.log("pixelDeltaX, pixelDeltaY", pixelDeltaX, pixelDeltaY);
        if (!this.panLocked) {
          console.log("here");

          this.translateX += pixelDeltaX / this.containerWidth;
          this.translateY += pixelDeltaY / this.containerHeight;
        }
      }
      this.pointerPosX = newMousePosX;
      this.pointerPosY = newMousePosY;
    },
    refreshContainerPos() {
      let rect = this.$refs.elm.getBoundingClientRect();
      this.containerLeft = rect.left;
      this.containerTop = rect.top;
    },
    onMouseDown(ev) {
      this.refreshContainerPos();
      this.isPointerDown = true;
      // Open the context menu then click other place will skip the mousemove events.
      // This will cause the pointerPosX/Y NOT sync, then we will need to fix it on mousedown event.
      this.pointerPosX = ev.clientX;
      this.pointerPosY = ev.clientY;
      console.log("onMouseDown", ev);
    },
    onMouseUp(ev) {
      this.isPointerDown = false;
      this.onInteractionEnd();
    },
    onMouseMove(ev) {
      this.onPointerMove(ev.clientX, ev.clientY);
      // console.log('onMouseMove client, offset', ev.clientX, ev.clientY)
    },
    zoomEnd(e) {
      var _ = e.getTransform();
      this.scale = _.scale;
    },
    async fetchJSON() {
      await this.$http
        .get(`/province.geojson`)
        .then(r => {
          this.d = r.data;
        })
        .catch(e => {})
        .finally(r => {});
    },
    dragHandler(e) {
      console.log(e);
    },
    loop() {
      this.animScale = this.gainOn(this.animScale, this.scale);
      this.animTranslateX = this.gainOn(this.animTranslateX, this.translateX);
      this.animTranslateY = this.gainOn(this.animTranslateY, this.translateY);
      this.raf = window.requestAnimationFrame(this.loop);
      // console.log('loop', this.raf)
    },
    gainOn(from, to) {
      let delta = (to - from) * 0.3;
      // console.log('gainOn', from, to, from + delta)
      if (Math.abs(delta) > 1e-5) {
        return from + delta;
      } else {
        return to;
      }
    }
  },
  computed: {
    wrapperStyle() {
      let translateX = this.containerWidth * this.animTranslateX;
      let translateY = this.containerHeight * this.animTranslateY;
      return {
        transform: [
          `translate(${translateX}px, ${translateY}px)`,
          `scale(${this.animScale})`
        ].join(" ")
      };
    },
    features() {
      if (!this.d) return [];
      return this.d.features.slice(0, 2);
    }
  },
  async mounted() {
    await this.fetchJSON();
    window.addEventListener("resize", this.onWindowResize);
    this.onWindowResize();
    this.refreshContainerPos();
    this.loop();
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