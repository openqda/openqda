<script setup>
import { onMounted, ref, watch } from 'vue';
import { debounce } from '../../../utils/dom/debounce.js';
import { cn } from '../../../utils/css/cn.js';
import {
  XMarkIcon,
  EllipsisHorizontalCircleIcon,
} from '@heroicons/vue/24/solid';

defineEmits(['remove']);
const props = defineProps([
  'sources',
  'codes',
  'checkedSources',
  'checkedCodes',
  'hasSelections',
  'api',
]);
const segments = ref(new Map());
const currentSources = ref([]);
const gridSize = ref(1);

const getSegmentsForFile = (file) => {
  const codes = props.codes.filter(
    (code) =>
      !!props.checkedCodes.get(code.id) &&
      (code.text ?? []).some((t) => t.source_id === file.id)
  );
  return codes
    .flatMap((code) => {
      return code.text
        .filter((t) => t.source_id === file.id)
        .map((segment) => ({ segment, color: rgba2hex(code.color) }));
    })
    .sort((a, b) => a.segment.start - b.segment.start);
};

const getColumns = (gridSize) => {
  switch (gridSize) {
    case 2:
      return 'grid-cols-2';
    case 3:
      return 'grid-cols-3';
    case 4:
      return 'grid-cols-4';
    case 5:
      return 'grid-cols-5';
    case 6:
      return 'grid-cols-6';
    default:
      return 'grid-cols-1';
  }
};

const filterFilesBySegments = () => {
  const updatedSources = [];
  props.sources.forEach((source) => {
    const c = getSegmentsForFile(source);
    if (c.length) {
      segments.value.set(source.id, c);
    } else {
      segments.value.delete(source.id);
    }
    if (props.checkedSources.get(source.id) && c.length) {
      updatedSources.push(source);
    }
  });
  currentSources.value = updatedSources;
};

watch(props, debounce(filterFilesBySegments, 100));

onMounted(() => {
  filterFilesBySegments();
});

// https://stackoverflow.com/questions/49974145/how-to-convert-rgba-to-hex-color-code-using-javascript
const rgba2hex = (color) => {
    if (color.startsWith('#')) return color
  let rgb = color
    .replace(/\s/g, '')
    .match(/^rgba?\((\d+),(\d+),(\d+),?([^,\s)]+)?/i);
  let hex = rgb
    ? (rgb[1] | (1 << 8)).toString(16).slice(1) +
      (rgb[2] | (1 << 8)).toString(16).slice(1) +
      (rgb[3] | (1 << 8)).toString(16).slice(1)
    : color;
  return `#${hex}`;
};
</script>
<template>
  <div class="block text-right">
    <label for="portrait-grid-size" class="mr-2">Columns</label>
    <input
      id="portrait-grid-size"
      class="rounded focus:ring-1 focus:ring-inset focus:ring-cerulean-700"
      type="number"
      v-model="gridSize"
      min="1"
      max="6"
    />
  </div>
  <div :class="cn(`grid gap-3 my-5`, getColumns(gridSize))">
    <div
      v-for="source in currentSources"
      class="border border-silver-300 p-2 col-span-1"
      :key="source.id"
    >
      <h3 class="font-semibold tracking-wide flex">
        <span class="truncate flex-grow">
          {{ source.name }}
        </span>
        <XMarkIcon
          class="float-right h-5 w-5 text-silver-300 hover:text-porsche-400 cursor-pointer"
          @click="$emit('remove', source.id)"
        />
      </h3>
      <div class="flex flex-wrap">
        <span
          v-for="(entry, index) in segments.get(source.id)"
          :key="`${source.id}-${index}`"
          :title="`${entry.segment.start}-${entry.segment.end};\n\n${entry.segment.text.substring(0, 250)}...`"
          class="m-1"
        >
          <EllipsisHorizontalCircleIcon
            :style="{
              color: entry.color,
              backgroundColor: entry.color,
            }"
            class="w-4 h-4"
          />
        </span>
      </div>
      <div v-if="!segments.has(source.id)" class="ml-2 mt-2 p-2 bg-silver-100">
        No codes
      </div>
    </div>
  </div>
</template>

<style scoped></style>
