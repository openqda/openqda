<script setup lang="ts">
import Plotly from 'plotly.js-basic-dist-min';
import { onMounted, ref, watch, inject } from 'vue';

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
]);

// just a helper function to insert line breaks in labels
function insertLineBreaks(label, maxLength, maxLines) {
  // Check for null or undefined and provide defaults
  if (typeof label !== 'string' || !label) return '';
  if (typeof maxLength !== 'number' || maxLength <= 0) maxLength = 20;
  if (typeof maxLines !== 'number' || maxLines <= 0) maxLines = 3;

  let result = '';
  let lineCount = 0;
  for (let i = 0; i < label.length && lineCount < maxLines; i += maxLength, lineCount++) {
    if (i > 0) result += '<br>';
    // If this is the last allowed line and there is more text, add ellipsis
    if (lineCount === maxLines - 1 && i + maxLength < label.length) {
      result += label.substring(i, i + maxLength - 1) + '…';
      break;
    } else {
      result += label.substring(i, i + maxLength);
    }
  }
  return result;
}

const API = inject('api');

const getCheckedSourceIds = () => {
  if (!props.checkedSources) {
    return [];
  }
  if (props.checkedSources.size === 0) {
    return [];
  }
  return Array.from(props.checkedSources.keys());
};

const getLabelOf = (sourceId) => {
  const source = props.sources.find((s) => s.id === sourceId);
  return source ? source.name : 'Unknown Source';
};

const rebuildList = () => {
  const codesList = {};
  const maxLengthOfLabel = 20; // Maximum length of label to display
  const maxLines = 3; // Maximum number of lines for label

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
  // console.log('codesList', codesList);

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
        opacity: 0.6,
        line: {
          color: 'rgb(8,48,107)',
          width: 1.5
        }
      },
      text: [], // Optional: Add text labels for each bar,
      hovertext: [], // Optional: Add hover text for each bar
      textposition: 'inside',
      hoverinfo: 'text',
    };

    // Populate the trace with data for each code
    Object.values(codesList)
      .toSorted((a, b) => a.name.localeCompare(b.name)) // Sort codes alphabetically
      .forEach(({ name, counts, color }) => {
        const multilineLabel = insertLineBreaks(name, maxLengthOfLabel, maxLines);
        trace.y.push(multilineLabel);
        
        trace.x.push(counts[sourceId] || 0); // Use 0 if no count for this source
        trace.marker.color.push(color); // Use the same color for all sources;
        trace.text.push(getLabelOf(sourceId)); // Bar label: document name only
        trace.hovertext.push(
          `Code: ${name.substring(0,50)}, Document: ${getLabelOf(sourceId).substring(0,30)}, Count: ${
            counts[sourceId] || 0
          }`
        ); // Hover info: full details
      });

    data.push(trace);
  });

  // Step 3: Define layout and render the chart
  const layout = {
    barmode: 'group', // grouped bar chart
    responsive: true,
    xaxis: {
      title: 'Count',
    },
    yaxis: {
      title: 'Codes',
      automargin: true, // Ensures enough space for long labels
      tickpadding: 20, // space between ticks and labels
    },
    showlegend: false, // Hide legend since we have labels on the bars

    margin: {
      l: 150, // Increase left margin to make room for labels
      r: 20, // Right margin
      t: 20, // Top margin
      b: 50, // Bottom margin
  },
  };

// step 4: Render the chart

  Plotly.newPlot(plotlyId.value, data, layout);
};


const plotlyId = ref(null);
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
