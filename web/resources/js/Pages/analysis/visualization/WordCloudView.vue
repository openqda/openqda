<template>
  <div class="p-3 mb-5">
    <table class="table-auto word-cloud-settings w-full">
      <thead>
        <tr>
          <th scope="col" class="text-left text-xs font-medium uppercase">
            Height
          </th>
          <th scope="col" class="text-left text-xs font-medium uppercase">
            Min word length
          </th>
          <th scope="col" class="text-left text-xs font-medium uppercase">
            Scale
          </th>
          <th scope="col" class="text-left text-xs font-medium uppercase">
            + Size
          </th>
          <th scope="col" class="text-left text-xs font-medium uppercase">
            <Cog6ToothIcon
              v-if="generating"
              class="animate-spin h-6 w-6 text-cerulean-700"
            />
          </th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <input type="number" v-model="minHeight" min="100" max="2000" />
          </td>
          <td>
            <input type="number" v-model="minWords" min="1" />
          </td>
          <td>
            <input
              type="range"
              v-model="scaleFactor"
              min="1"
              max="50"
              step="1"
            />
            <span>{{ scaleFactor }}</span>
          </td>
          <td>
            <input type="range" v-model="scaleAdd" min="0" max="50" step="1" />
            <span>{{ scaleAdd }}</span>
          </td>
          <td>
            <Button @click="rebuild" :disabled="generating">Refresh</Button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div
    ref="resizeRef"
    class="cloud-root"
    :style="{ height: windowHeight + 'px' }"
  >
    <svg ref="svgRef" id="cloud-svg">
      <g ref="gRef"></g>
    </svg>
  </div>

  <div class="text-center m-4">
    <span class="p-2 border-t">{{ words.size }} unique words</span>
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
import { onMounted, ref, watch, watchEffect } from 'vue';
import { debounce } from '../../../utils/dom/debounce.js';
import * as d3Module from 'd3';
import * as cloudModule from 'd3-cloud';
import { useResizeObserver } from '../resizeObserver.js';
import Button from '../../../Components/interactive/Button.vue';
import { Cog6ToothIcon } from '@heroicons/vue/20/solid';

const d3 = d3Module.default ?? d3Module;
const cloud = cloudModule.default ?? cloudModule;
const props = defineProps([
  'sources',
  'codes',
  'checkedSources',
  'checkedCodes',
  'hasSelections',
  'api',
]);

const svgRef = ref(null);
const gRef = ref(null);
const { resizeRef, resizeState } = useResizeObserver();
const words = ref(new Map());

// options
const minWords = ref(4);
const underThresholdTransparency = ref(1.0);
const scaleFactor = ref(12);
const scaleAdd = ref(3);
const seed = ref(Math.random());
const generating = ref(false);
const minHeight = ref(500);
const windowHeight = ref(500);

function rebuild() {
  seed.value = Math.random();
}

watch(
  minHeight,
  debounce((val) => {
    windowHeight.value = val;
  }, 500)
);

onMounted(() => {
  const svg = d3.select(svgRef.value);
  const g = d3.select(gRef.value);
  const layout = cloud();

  function draw(words, layout) {
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
      .style('fill', function (d) {
        return randomColor(d.text);
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

  const _setupDebounced = debounce(({ width, height, wordsList, scale }) => {
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
      .on('end', (words) => draw(words, layout));

    layout.start();
  }, 500);

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
    const wordsList = [...words.value.entries()];
    const scale = {
      factor: Number(scaleFactor.value),
      addition: Number(scaleAdd.value),
    };
    setup({ width, height, wordsList, scale, seed: seed.value });
  });

  watchEffect(() => {
    const { sources, codes, checkedSources, checkedCodes } = props;
    words.value.clear();

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
                    if (word.length < minWords.value) {
                      return;
                    }
                    const map = words.value;
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
  });
});

const randomColor = (word) => {
  return word.length > minWords.value
    ? `hsla(${Math.random() * 360}, 75%, 50%, 1)`
    : `hsla(${Math.random() * 360}, 75%, 85%, ${underThresholdTransparency.value})`;
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
