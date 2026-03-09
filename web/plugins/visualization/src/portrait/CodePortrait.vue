<script setup>
import { onMounted, ref, watch, inject } from 'vue';
import {
  XMarkIcon,
  EllipsisHorizontalCircleIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/solid';

defineEmits(['remove']);
const props = defineProps([
  'sources',
  'codes',
  'checkedSources',
  'checkedCodes',
  'hasSelections',
  'menu',
  'showMenu',
]);

const API = inject('api');
const { Headline3, Collapse } = inject('components');
const segments = ref(new Map());
const currentSources = ref([]);
const gridSize = ref(1);
const scale = ref(1);
const gap = ref(1);
const radius = ref(0);
const hoverPortrait = ref({});
const sourcesCollapse = ref({});

const getSelectionssFor = (source) => {
  const codes = props.codes.filter(
    (code) =>
      !!props.checkedCodes.get(code.id) &&
      (code.text ?? []).some((t) => t.source_id === source.id)
  );
  return codes
    .flatMap((code) => {
      return code.text
        .filter((t) => t.source_id === source.id)
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
    const c = getSelectionssFor(source);
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

watch(props, API.debounce(filterFilesBySegments, 100), {
  immediate: true,
  deep: true,
});

onMounted(() => {
  filterFilesBySegments();
});

// https://stackoverflow.com/questions/49974145/how-to-convert-rgba-to-hex-color-code-using-javascript
const rgba2hex = (color) => {
  if (color.startsWith('#')) return color;
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
  <div>
    <component
      :is="props.menu"
      title="Code Portrait options"
      :show="props.showMenu"
      @close="API.setShowMenu(false)"
    >
      <ul class="p-4 flex flex-col gap-4">
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            Columns
          </label>
          <input
            id="portrait-grid-size"
            class="w-full rounded focus:ring-1 focus:ring-inset focus:ring-primary"
            type="number"
            v-model="gridSize"
            min="1"
            max="6"
          />
        </li>
        <li>
          <label
            class="text-left text-xs font-medium uppercase w-full flex justify-between"
          >
            <span>Scale</span>
            <span>{{ scale }}</span>
          </label>
          <input
            type="range"
            v-model="scale"
            min="0.5"
            max="10"
            step="0.5"
            class="w-full"
          />
        </li>
        <li>
          <label
            class="text-left text-xs font-medium uppercase w-full flex justify-between"
          >
            <span>Gap</span>
            <span>{{ gap }}</span>
          </label>
          <input
            type="range"
            v-model="gap"
            min="0"
            max="5"
            step="1"
            class="w-full"
          />
        </li>
        <li>
          <label
            class="text-left text-xs font-medium uppercase w-full flex justify-between"
          >
            <span>Radius</span>
            <span>{{ radius }}</span>
          </label>
          <input
            type="range"
            v-model="radius"
            min="0"
            max="5"
            step="1"
            class="w-full"
          />
        </li>
      </ul>
    </component>
    <div class="block w-full">
      <div :class="API.cn(`grid gap-3 my-5`, getColumns(gridSize))">
        <div
          v-for="source in currentSources"
          :key="source.id"
          :class="
            API.cn(
              'border p-2 col-span-1 shadow',
              hoverPortrait[source.id] ? 'border-primary' : 'border-border'
            )
          "
          @mouseenter="hoverPortrait[source.id] = true"
          @mouseleave="hoverPortrait[source.id] = false"
        >
          <Headline3
            :class="
              API.cn(
                'flex items-center justify-between',
                gap == 1 && 'mx-1',
                gap == 2 && 'mx-2',
                gap == 3 && 'mx-3',
                gap == 4 && 'mx-4',
                gap == 5 && 'mx-5'
              )
            "
          >
            <button
              @click="sourcesCollapse[source.id] = !sourcesCollapse[source.id]"
              class="truncate flex-grow flex items-center"
            >
              <ChevronRightIcon
                :class="
                  API.cn(
                    'w-4 h-4 transition-all duration-300 transform',
                    sourcesCollapse[source.id] && 'rotate-90'
                  )
                "
              />
              <span>{{ source.name }}</span>
            </button>
            <XMarkIcon
              class="h-5 w-5 cursor-pointer"
              @click="$emit('remove', source.id)"
            />
          </Headline3>
          <Collapse :when="sourcesCollapse[source.id]">
            <div v-if="sourcesCollapse[source.id]" class="flex flex-wrap">
              <span
                v-for="entry in segments.get(source.id)"
                :key="`${source.id}-${entry.segment.id}`"
                :title="`${entry.segment.start}-${entry.segment.end};\n\n${entry.segment.text.substring(0, 250)}...`"
                :class="
                  API.cn(
                    gap == 1 && 'm-1',
                    gap == 2 && 'm-2',
                    gap == 3 && 'm-3',
                    gap == 4 && 'm-4',
                    gap == 5 && 'm-5'
                  )
                "
              >
                <EllipsisHorizontalCircleIcon
                  :class="
                    API.cn(
                      radius == 1 && 'rounded-sm',
                      radius == 2 && 'rounded-md',
                      radius == 3 && 'rounded-xl',
                      radius == 4 && 'rounded-2xl',
                      radius == 5 && 'rounded-full'
                    )
                  "
                  :style="{
                    color: entry.color,
                    backgroundColor: entry.color,
                    height: `${scale}rem`,
                  }"
                />
              </span>
            </div>
            <div
              v-if="!segments.has(source.id)"
              class="ml-2 mt-2 p-2 bg-silver-100"
            >
              No codes
            </div>
          </Collapse>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
