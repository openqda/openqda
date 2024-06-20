<template>
  <div class="block text-right">
    <label for="portrait-grid-size" class="mr-2">Columns</label>
    <input
      id="portrait-grid-size"
      class="rounded focus:ring-1 focus:ring-inset focus:ring-cerulean-700"
      type="number"
      v-model="gridSize"
      min="1"
      max="12"
    />
  </div>
  <div :class="`grid grid-cols-${gridSize} gap-3 my-5`">
    <div
      v-for="file in currentFiles"
      class="border border-silver-300 p-2 col-span-1"
      :key="file.id"
    >
      <h3 class="font-semibold tracking-wide flex">
        <span class="truncate flex-grow">
          {{ file.name }}
        </span>
        <XMarkIcon
          class="float-right h-5 w-5 text-silver-300 hover:text-porsche-400 cursor-pointer"
          @click="$emit('remove', file.id)"
        />
      </h3>
      <div class="flex flex-wrap">
        <span
          v-for="(entry, index) in segments.get(file.id)"
          :key="`${file.id}-${index}`"
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
      <div v-if="!segments.has(file.id)" class="ml-2 mt-2 p-2 bg-silver-100">
        No codes
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { debounce } from '../../utils/dom/debounce.js';
import {
  XMarkIcon,
  EllipsisHorizontalCircleIcon,
} from '@heroicons/vue/24/solid';

defineEmits(['remove']);
const props = defineProps([
  'files',
  'codes',
  'checkedFiles',
  'checkedCodes',
  'hasSelections',
  'api',
]);
const segments = ref(new Map());
const currentFiles = ref([]);
const gridSize = ref(2);

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

const filterFilesBySegments = () => {
  const updatedFiles = [];
  props.files.forEach((file) => {
    const c = getSegmentsForFile(file);
    if (c.length) {
      segments.value.set(file.id, c);
    } else {
      segments.value.delete(file.id);
    }
    if (props.checkedFiles.get(file.id)) {
      updatedFiles.push(file);
    }
  });
  currentFiles.value = updatedFiles;
};

watch(props, debounce(filterFilesBySegments, 100));

onMounted(() => {
  filterFilesBySegments();
});

// https://stackoverflow.com/questions/49974145/how-to-convert-rgba-to-hex-color-code-using-javascript
const rgba2hex = (color) => {
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

<style scoped></style>
