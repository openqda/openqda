<script setup lang="ts">
import Plotly from 'plotly.js-basic-dist-min';
import { onMounted, ref, watch, inject, watchEffect } from 'vue';
import { insertLineBreaks, getSorter } from './helpers.js';

/**
 * Simple Grouped Bar Chart visualization plugin that uses
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
  'menu',
  'showMenu',
]);

const plotlyId = ref(null);
const ready = ref(false);
const API = inject('api');
const options = ref({
  margin: 10,
  barmode: 'group',
  opacity: 0.6,
  borderWidth: 1.5,
  maxLengthOfLabel: 20,
  maxLines: 3,
  build: 'source', // or 'all'
  sort: 'name', // or 'count'
  sortDir: 'ascending',
});

// Resize Observer to make the plotly chart responsive on window resize
const { resizeRef, resizeState } = API.useResizeObserver();

// TODO move into API
const getCheckedSourceIds = () => {
  if (!props.checkedSources) {
    return [];
  }
  if (props.checkedSources.size === 0) {
    return [];
  }
  return Array.from(props.checkedSources.keys());
};

// TODO move into API
const getLabelOf = (sourceId) => {
  const source = props.sources.find((s) => s.id === sourceId);
  return source ? source.name : 'Unknown Source';
};

const rebuildList = (plotlyOptions) => {
  if (plotlyOptions.build === 'source') {
    buildBySource(plotlyOptions);
  } else {
    buildAll(plotlyOptions);
  }
};

const buildBySource = (plotlyOptions) => {
  const { opacity, borderWidth, maxLengthOfLabel, maxLines, sort, sortDir } =
    plotlyOptions;
  const codesList = {};

  // Step 1: Build the codesList with counts per source
  API.eachCheckedSources((source) => {
    API.eachCheckedCodes((code) => {
      if (!codesList[code.id]) {
        codesList[code.id] = { name: code.name, counts: {}, color: code.color };
      }

      // Initialize the count for this source if it doesn't exist
      if (!codesList[code.id].counts[source.id]) {
        codesList[code.id].counts[source.id] = 0;
      }

      // Increment the count for this source if the selection matches
      for (const selection of code.text) {
        if (source.id === selection.source_id) {
          codesList[code.id].counts[source.id]++;
        }
      }
    });
  });
  // Step 2: Prepare the data object for Plotly
  const data = [];
  const sourceIds = getCheckedSourceIds(); // get all checked source IDs.

  // Create a Plotly trace (horizontal bar) for each source
  sourceIds.forEach((sourceId) => {
    // Initialize a trace for this source
    const trace = {
      x: [],
      y: [],
      name: `${getLabelOf(sourceId)}`, // Label for the source
      type: 'bar',
      orientation: 'h',
      marker: {
        color: [], // Optional: Add specific colors for each source
        opacity,
        line: {
          color: 'rgb(8,48,107)',
          width: borderWidth,
        },
      },
      text: [], // Optional: Add text labels for each bar,
      hovertext: [], // Optional: Add hover text for each bar
      textposition: 'inside',
      hoverinfo: 'text',
    };

    // Populate the trace with data for each code
    Object.values(codesList)
      .toSorted(getSorter(sort, sortDir, true))
      .forEach(({ name, counts, color }) => {
        const multilineLabel = insertLineBreaks(
          name,
          Number(maxLengthOfLabel),
          Number(maxLines)
        );
        trace.y.push(multilineLabel);

        trace.x.push(counts[sourceId] || 0); // Use 0 if no count for this source
        trace.marker.color.push(color); // Use the same color for all sources;
        trace.text.push(getLabelOf(sourceId)); // Bar label: document name only
        trace.hovertext.push(
          `Code: ${name.substring(0, 50)}, Document: ${getLabelOf(sourceId).substring(0, 30)}, Count: ${
            counts[sourceId] || 0
          }`
        ); // Hover info: full details
      });

    data.push(trace);
  });

  // Step 3: Define layout and render the chart
  const layout = getLayout(plotlyOptions);

  // step 4: Render the chart
  Plotly.newPlot(plotlyId.value, data, layout);
};

const getLayout = (plotlyOptions) => {
  const { margin, barmode } = plotlyOptions;
  return {
    barmode,
    responsive: true,
    xaxis: {
      title: 'Count',
      automargin: true,
      tickpadding: 20, // space between ticks and labels
    },
    yaxis: {
      title: 'Codes',
      automargin: true, // Ensures enough space for long labels
      tickpadding: 20, // space between ticks and labels
    },
    showlegend: false, // Hide legend since we have labels on the bars

    margin: {
      l: margin, // Increase left margin to make room for labels
      r: margin, // Right margin
      t: margin, // Top margin
      b: margin, // Bottom margin
    },
    autosize: true,
  };
};

const buildAll = (plotlyOptions) => {
  const { opacity, borderWidth, maxLengthOfLabel, maxLines, sort, sortDir } =
    plotlyOptions;
  const codesList = {};

  API.eachCheckedSources((source) => {
    API.eachCheckedCodes((code) => {
      if (!codesList[code.id]) {
        codesList[code.id] = { name: code.name, counts: 0, color: code.color };
      }

      for (const selection of code.text) {
        if (source.id === selection.source_id) {
          codesList[code.id].counts++;
        }
      }
    });
  });

  const trace = {
    x: [],
    y: [],
    type: 'bar',
    orientation: 'h',
    marker: {
      color: [], // Optional: Add specific colors for each source
      opacity,
      line: {
        color: 'rgb(8,48,107)',
        width: borderWidth,
      },
    },
    text: [], // Optional: Add text labels for each bar,
    hovertext: [], // Optional: Add hover text for each bar
    textposition: 'inside',
    hoverinfo: 'text',
  };
  Object.values(codesList)
    .toSorted(getSorter(sort, sortDir))
    .forEach(({ name, counts, color }) => {
      const multilineLabel = insertLineBreaks(
        name,
        Number(maxLengthOfLabel),
        Number(maxLines)
      );
      trace.y.push(multilineLabel);
      trace.x.push(counts);
      trace.marker.color.push(color);
      trace.hovertext.push(
        `Code: ${name.substring(0, 50)}, Count: ${counts || 0}`
      ); // Hover info: full details
    });
  const data = [trace];
  // Step 3: Define layout and render the chart
  const layout = getLayout(plotlyOptions);
  const config = { responsive: true };
  Plotly.newPlot(plotlyId.value, data, layout, config);
};

/* ----------------------------------------------------------
 * Watch for changes in props and options to rebuild the list
 * but use debounce to avoid excessive calls.
 */

const _rebuildList = API.debounce(rebuildList, 100);

watch(props, () => _rebuildList(options.value), {
  immediate: true,
  deep: true,
});
watchEffect(() => {
  // this will trigger on any options change
  const { width, height } = resizeState.dimensions;
  const opts = options.value;
  _rebuildList({ width, height, ...opts });
});

onMounted(() => {
  ready.value = true;
  rebuildList(options.value);
});
</script>

<template>
  <div>
    <component
      :is="props.menu"
      title="Code Selection Count Options"
      :show="props.showMenu"
      @close="API.setShowMenu(false)"
    >
      <ul class="p-4 flex flex-col gap-4">
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            Counting Mode
          </label>
          <select v-model="options.build" class="w-full">
            <option value="all">By Codes</option>
            <option value="source">By Source</option>
          </select>
        </li>
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            Bar Style
          </label>
          <select v-model="options.barmode" class="w-full">
            <option value="group">Grouped</option>
            <option value="stack">Stacked</option>
            <option value="overlay">Overlay</option>
            <option value="relative">Relative</option>
          </select>
        </li>
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            Sorting Mode
          </label>
          <select v-model="options.sort" class="w-full">
            <option value="name">By Name</option>
            <option value="count">By Count</option>
          </select>
          <select v-model="options.sortDir" class="w-full mt-1">
            <option value="ascending">Ascending</option>
            <option value="descending">Descending</option>
          </select>
        </li>
        <li>
          <label
            class="text-left text-xs font-medium uppercase w-full flex justify-between"
          >
            <span>Margin</span>
            <span>{{ options.margin }}</span>
          </label>
          <input
            type="range"
            v-model="options.margin"
            min="0"
            max="250"
            step="5"
            class="w-full"
          />
        </li>
        <li>
          <label
            class="text-left text-xs font-medium uppercase w-full flex justify-between"
          >
            <span>Opacity</span>
            <span>{{ options.opacity }}</span>
          </label>
          <input
            type="range"
            v-model="options.opacity"
            min="0"
            max="1"
            step="0.1"
            class="w-full"
          />
        </li>
        <li>
          <label
            class="text-left text-xs font-medium uppercase w-full flex justify-between"
          >
            <span>Border Width</span>
            <span>{{ options.borderWidth }}</span>
          </label>
          <input
            type="range"
            v-model="options.borderWidth"
            min="0"
            max="10"
            step="0.5"
            class="w-full"
          />
        </li>
        <li>
          <label
            class="text-left text-xs font-medium uppercase w-full flex justify-between"
          >
            <span>Max Y-Axis Label Width</span>
            <span>{{ options.maxLengthOfLabel }}</span>
          </label>
          <input
            type="range"
            v-model="options.maxLengthOfLabel"
            min="5"
            max="200"
            step="5"
            class="w-full"
          />
        </li>
        <li>
          <label
            class="text-left text-xs font-medium uppercase w-full flex justify-between"
          >
            <span>Max Y-Axis Label Lines</span>
            <span>{{ options.maxLines }}</span>
          </label>
          <input
            type="range"
            v-model="options.maxLines"
            min="1"
            max="5"
            step="1"
            class="w-full"
          />
        </li>
      </ul>
    </component>
    <div ref="resizeRef" class="h-full w-full border border-border">
      <div ref="plotlyId" name="plotly"></div>
    </div>
  </div>
</template>

<style scoped></style>
