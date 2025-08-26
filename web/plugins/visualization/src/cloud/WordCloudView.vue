<template>
  <div>
    <component
      :is="props.menu"
      title="Word Cloud options"
      :show="props.showMenu"
      @close="API.setShowMenu(false)"
    >
      <ul class="p-4 flex flex-col gap-4">
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            Height
          </label>
          <input
            type="number"
            v-model="minHeight"
            min="100"
            max="2000"
            class="w-full"
          />
        </li>
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            Min word length
          </label>
          <input type="number" v-model="minWords" min="1" class="w-full" />
        </li>
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            <span>Scale</span>
            <span class="float-end">{{ scaleFactor }}</span>
          </label>
          <input
            type="range"
            v-model="scaleFactor"
            min="1"
            max="50"
            step="1"
            class="w-full"
          />
        </li>
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            <span>Size</span>
            <span class="float-end">{{ scaleAdd }}</span>
          </label>
          <input
            type="range"
            v-model="scaleAdd"
            min="0"
            max="50"
            step="1"
            class="w-full"
          />
        </li>
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            <span>Saturation</span>
            <span class="float-end">{{ saturation }}</span>
          </label>
          <input
            type="range"
            v-model="saturation"
            min="0"
            max="100"
            step="1"
            class="w-full"
          />
        </li>
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            <span>Lighting</span>
            <span class="float-end">{{ lighting }}</span>
          </label>
          <input
            type="range"
            v-model="lighting"
            min="0"
            max="100"
            step="1"
            class="w-full"
          />
        </li>
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            Include Words
          </label>
          <textarea
            type="string"
            v-model="includes"
            rows="2"
            class="w-full resize-y p-1"
            placeholder="...one word per line"
          ></textarea>
        </li>
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            Exclude Words
          </label>
          <textarea
            type="string"
            v-model="excludes"
            rows="2"
            class="w-full resize-y p-1"
            placeholder="...one word per line"
          ></textarea>
        </li>
        <li>
          <Button @click="rebuild" :disabled="generating">
            <Cog6ToothIcon
              v-if="generating"
              class="animate-spin h-6 w-6 text-cerulean-700"
            />
            <span v-else>Force Refresh</span>
          </Button>
        </li>
      </ul>
      <ul class="p-4 flex flex-col gap-4">
        <li>
          <label
            class="text-left text-xs font-medium uppercase w-full flex justify-between"
          >
            <span>All words</span>
            <span>{{ words.size }}</span>
          </label>
          <textarea
            type="string"
            v-model="wordsList"
            rows="4"
            class="w-full resize-y p-1"
            placeholder="...one word per line"
            readonly
          ></textarea>
        </li>
      </ul>
    </component>
    <div class="w-full block">
      <Cog6ToothIcon
        v-if="generating"
        class="animate-spin h-6 w-6 text-cerulean-700"
      />
      <div
        ref="resizeRef"
        class="cloud-root border border-border"
        :style="{ height: windowHeight + 'px' }"
      >
        <svg ref="svgRef" id="cloud-svg">
          <g ref="gRef"></g>
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
/*-------------------------------------------------------------------------
 * This component demonstrates a basic visualization plugin with d3,
 * rendering a configurable word-cloud from selections
 * Resources:
 * https://dev.to/muratkemaldar/using-vue-3-with-d3-composition-api-3h1g
 * https://github.com/jasondavies/d3-cloud
 *-----------------------------------------------------------------------*/
import { onMounted, ref, watch, watchEffect, inject } from 'vue';
import * as d3Module from 'd3';
import * as cloudModule from 'd3-cloud';
import { Cog6ToothIcon } from '@heroicons/vue/20/solid';

const API = inject('api');
const { Button } = inject('components');
const d3 = d3Module.default ?? d3Module;
const cloud = cloudModule.default ?? cloudModule;
const props = defineProps([
  'sources',
  'codes',
  'checkedSources',
  'checkedCodes',
  'hasSelections',
  'api',
  'menu',
  'showMenu',
]);

const svgRef = ref(null);
const gRef = ref(null);
const { resizeRef, resizeState } = API.useResizeObserver();
const words = ref(new Map());
const wordsList = ref('');

// options
const minWords = ref(4);
const scaleFactor = ref(12);
const scaleAdd = ref(3);
const seed = ref(Math.random());
const generating = ref(false);
const minHeight = ref(500);
const windowHeight = ref(500);
const includes = ref('');
const excludes = ref('');
const saturation = ref(75);
const lighting = ref(50);

function rebuild() {
  seed.value = Math.random();
}

watch(
  minHeight,
  API.debounce((val) => {
    windowHeight.value = val;
  }, 500)
);

onMounted(() => {
  const svg = d3.select(svgRef.value);
  const g = d3.select(gRef.value);
  const layout = cloud();

  function draw(words, layout, colors) {
    svg.attr('width', layout.size()[0]).attr('height', layout.size()[1]);
    g.selectAll('*').remove();
    g.attr(
      'transform',
      'translate(' + layout.size()[0] / 2 + ',' + layout.size()[1] / 2 + ')'
    )
      .selectAll('text')
      .data(words)
      .enter()
      .append('text')
      .style('font-size', function (d) {
        return d.size + 'px';
      })
      .style('fill', function () {
        return randomColor(colors.saturation, colors.lighting);
      })
      .style('font-family', 'Impact')
      .attr('text-anchor', 'middle')
      .attr('transform', function (d) {
        return 'translate(' + [d.x, d.y] + ')rotate(' + d.rotate + ')';
      })
      .text(function (d) {
        return d.text;
      });

    generating.value = false;
  }

  const _setupDebounced = API.debounce(
    ({ width, height, wordsList, scale, colors }) => {
      layout
        .size([width, height])
        .words(
          wordsList.map(function ([d, count]) {
            return {
              text: d,
              size: count * scale.factor + scale.addition,
            };
          })
        )
        .padding(5)
        .rotate(function () {
          return ~~(Math.random() * 2) * 90;
        })
        .font('Impact')
        .fontSize(function (d) {
          return d.size;
        })
        .on('end', (words) => draw(words, layout, colors));

      layout.start();
    },
    500
  );

  const setup = (options) => {
    // always keep the loading icon active
    // but run the actual updated in a debounced mode
    // to support fast changes with few renders
    if (words.value.size > 0) {
      generating.value = true;
    }
    _setupDebounced(options);
  };

  watchEffect(() => {
    const { width, height } = resizeState.dimensions;
    const colors = {
      saturation: Number(saturation.value),
      lighting: Number(lighting.value),
    };
    const excluded = wordsToSet(excludes.value);
    const included = wordsToSet(includes.value);
    const wordsList = [...words.value.entries()].filter((w) => {
      const word = w[0].toLowerCase();
      if (excluded.has(word)) {
        return false;
      }
      if (included.has(word)) {
        return true;
      }
      return w[0].length >= minWords.value;
    });
    const scale = {
      factor: Number(scaleFactor.value),
      addition: Number(scaleAdd.value),
    };
    setup({ width, height, wordsList, scale, seed: seed.value, colors });
  });

  watchEffect(() => {
    const { sources, codes, checkedSources, checkedCodes } = props;
    words.value.clear();

    const list = [];

    for (const source of sources) {
      if (checkedSources.get(source.id)) {
        for (const code of codes) {
          if (
            checkedCodes.get(code.id) &&
            code.text.some((t) => t.source_id === source.id)
          ) {
            for (const selection of code.text) {
              if (source.id === selection.source_id) {
                selection.text
                  .replace(/[^\w\s]+/g, '')
                  .split(/\s+/g)
                  .forEach((word) => {
                    const map = words.value;
                    if (!map.has(word)) {
                      map.set(word, 0);
                      list.push(word);
                    }
                    map.set(word, map.get(word) + 1);
                  });
              }
            }
          }
        }
      }
    }

    list.sort((a, b) => a.localeCompare(b));
    wordsList.value = list.join('\n');
  });
});

const wordsToSet = (list) => {
  return new Set(
    (list ?? '')
      .split('\n')
      .map((w) => w.trim().toLowerCase())
      .filter((w) => w.length > 0)
  );
};

const randomColor = (s, l) => {
  return `hsla(${Math.random() * 360}, ${s}%, ${l}%, 1)`;
};
</script>

<style scoped>
#cloud-svg {
  /* important for responsiveness */
  display: block;
  fill: none;
  stroke: none;
  width: 100%;
  height: 100%;
  overflow: visible;
  background: transparent;
}

.word-cloud-settings td {
  padding-top: 1.2rem;
  padding-bottom: 1.2rem;
}

.cloud-root {
  overflow: hidden;
}
</style>
