<template>
  <div :id="plotlyId"></div>
</template>

<script setup>
import { ref, onMounted, watchEffect } from 'vue';
import * as Plotly from 'plotly.js-dist';

const props = defineProps({
  data: {
    type: Array,
    required: true,
  },
  layout: {
    type: Object,
    required: true,
  },
  config: {
    type: Object,
    required: false,
    default: () => ({ responsive: true }),
  },
});

const plotlyId = ref(null);
const ready = ref(false);

function setGraph() {
  if (!ready.value || !plotlyId.value || !props.data || !props.layout) {
    return;
  }
  Plotly.newPlot(plotlyId.value, props.data, props.layout, props.config);
}

onMounted(() => {
  ready.value = true;
  setGraph();
});

watchEffect(() => {
  setGraph();
});
</script>

<style scoped></style>
