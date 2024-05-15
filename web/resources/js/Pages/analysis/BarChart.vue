<template>
  <VuePlotly
    :data="data"
    :layout="layout"
    :display-mode-bar="false"
  ></VuePlotly>
</template>

<script setup>
import { VuePlotly } from 'vue3-plotly';
import { ref, watchEffect } from 'vue';

const props = defineProps([
  'files',
  'codes',
  'checkedFiles',
  'checkedCodes',
  'hasSelections',
  'api',
]);
const data = ref(null);
const layout = {
  title: 'My graph',
  height: 800,
};

// options / config
const minWords = ref(1);

watchEffect(() => {
  const { files, codes, checkedFiles, checkedCodes } = props;
  const newData = {
    x: [],
    y: [],
    type: 'bar',
    orientation: 'h',
    marker: {
      height: 5,
      width: 5,
    },
  };
  const map = new Map();
  for (const file of files) {
    if (checkedFiles.get(file.id)) {
      for (const code of codes) {
        if (
          checkedCodes.get(code.id) &&
          code.texts.some((t) => t.source_id === file.id)
        ) {
          for (const selection of code.texts) {
            if (file.id === selection.source_id) {
              selection.text
                .replace(/[^\w\s]+/g, '')
                .split(/\s+/g)
                .forEach((word) => {
                  if (word.length < minWords.value) {
                    return;
                  }
                  if (!map.has(word)) {
                    map.set(word, 0);
                  }
                  map.set(word, map.get(word) + 1);
                });
            }
          }
        }
      }
    }
  }

  const entries = [...map.entries()];
  entries.sort((a, b) => a[1] - b[1]);

  for (const [word, count] of entries) {
    newData.y.push(word);
    newData.x.push(count);
  }
  data.value = [newData];
});
</script>

<style scoped></style>
