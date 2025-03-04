<script setup lang="ts">
import Plotly from 'plotly.js-basic-dist-min';
import { onMounted, ref, watch, inject } from 'vue';

/**
 * Simple Bar Chart visualization plugin that uses
 * minimal plotly:
 * https://plotly.com/javascript/
 */

defineEmits(['remove']);
const props = defineProps([
  'sources',
  'codes',
  'checkedSources',
  'checkedCodes',
  'hasSelections',
]);

const API = inject('api');

const rebuildList = () => {
  const codesList = {};

  API.eachCheckedSources((source) => {
    API.eachCheckedCodes((code) => {
      if (!codesList[code.id]) {
        codesList[code.id] = { name: code.name, count: 0, color: code.color };
      }

      for (const selection of code.text) {
        if (source.id === selection.source_id) {
          codesList[code.id].count++;
        }
      }
    });
  });

  const trace1 = {
    x: [],
    y: [],
    type: 'bar',
    orientation: 'h',
    marker: {
      color: [],
    },
  };
  Object.values(codesList)
    .toSorted((a, b) => a.count - b.count)
    .forEach(({ name, count, color }) => {
      trace1.y.push(`→ ${name}`);
      trace1.x.push(count);
      trace1.marker.color.push(color);
    });
  const data = [trace1];
  const layout = {};
  console.debug(trace1);
  const config = { responsive: true };
  Plotly.newPlot(plotlyId.value, data, layout, config);
};

const plotlyId: Ref<null | HTMLDivElement> = ref(null);
const ready = ref(false);
watch(props, API.debounce(rebuildList, 100), { immediate: true, deep: true });

onMounted(() => {
  ready.value = true;
  rebuildList();
});
</script>

<template>
  <div ref="plotlyId" name="plotly"></div>
</template>

<style scoped></style>
