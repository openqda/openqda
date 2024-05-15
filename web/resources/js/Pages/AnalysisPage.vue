<template>
  <AppLayout>
    <div class="lg:flex">
      <!-- left - content preview -->
      <div class="w-full lg:w-2/3 p-4" id="analysis-root">
        <Headline2>Output</Headline2>

        <component
          :is="visualizerComponent"
          :files="files"
          :api="VisualizationPluginAPI"
          :codes="allCodes"
          :hasSelections="hasSelections"
          :checkedCodes="checkedCodes"
          :checkedFiles="checkedFiles"
          @remove="(id) => checkFile(undefined, id)"
        />
      </div>
      <!-- right side -->
      <div class="w-full lg:w-1/3 p-4">
        <Headline2 class="mb-3">
          <span>Code Selections</span>
          <span class="float-right">
            <Button
              @click="saveCSV()"
              color="cerulean"
              :disabled="!hasSelections"
              :icon="ArrowDownTrayIcon"
              label="CSV"
            />
          </span>
        </Headline2>
        <p class="mt-1 text-sm leading-6 mb-4">
          Select the files and codes to display your code selections.
        </p>

        <div>
          <select
            id="location"
            name="location"
            @change="selectVisualizerPlugin($event.target)"
            class="mt-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
          >
            <option v-for="(plugin, pluginIndex) in availablePlugins" :value="plugin.name" :key="pluginIndex">
              {{ plugin.title }}
            </option>
          </select>
        </div>

        <table class="w-full mt-4 border-collapse border-0">
          <thead>
            <tr>
              <th style="width: 3rem"></th>
              <th
                scope="col"
                class="p-2 text-xs text-left font-medium uppercase tracking-wide text-silver-300 sm:pl-0"
              >
                Files
              </th>
            </tr>
          </thead>
          <tbody>
            <tr class="text-sm hover:bg-silver-300">
              <td class="py-2 text-center tracking-wide">
                <input
                  id="all_files"
                  type="checkbox"
                  class="cursor-pointer"
                  :checked="checkedFiles.get('all_files')"
                  @change="checkFile($event, 'all_files')"
                />
              </td>
              <td class="py-2 tracking-wide">
                <label for="all_files" class="cursor-pointer select-none"
                  >All files</label
                >
              </td>
            </tr>
            <tr
              v-for="file in files"
              :key="file.id"
              class="text-sm hover:bg-silver-300"
            >
              <td class="py-2 text-center tracking-wide">
                <input
                  type="checkbox"
                  class="cursor-pointer"
                  :checked="checkedFiles.get(file.id)"
                  @change="checkFile($event, file.id)"
                />
              </td>
              <td
                class="py-2 tracking-wide"
                @click="checkFile($event, file.id)"
              >
                <label :for="file.id" class="cursor-pointer select-none">{{
                  file.name
                }}</label>
              </td>
            </tr>
          </tbody>
        </table>

        <table class="w-full mt-4 border-collapse border-0">
          <thead>
            <tr class="border-0">
              <th style="width: 3rem"></th>
              <th
                scope="col"
                class="p-2 text-left text-xs font-medium uppercase tracking-wide text-silver-300 sm:pl-0"
              >
                Codes
              </th>
            </tr>
          </thead>
          <tbody>
            <tr class="text-sm border-0 hover:bg-silver-300">
              <td class="py-2 text-center tracking-wide">
                <input
                  id="all_codes"
                  type="checkbox"
                  :checked="checkedCodes.get('all_codes')"
                  @change="checkCode($event, 'all_codes')"
                />
              </td>
              <td class="py-2 tracking-wide">
                <label for="all_codes">All Codes</label>
              </td>
            </tr>
            <tr
              v-for="(code) in allCodes"
              :key="code.id"
              class="text-sm border-0 hover:bg-silver-300"
            >
              <td class="py-2 text-center tracking-wide border-0">
                <input
                  :id="code.id"
                  type="checkbox"
                  :checked="checkedCodes.get(code.id)"
                  @change="checkCode($event, code.id)"
                  class="cursor-pointer"
                />
              </td>
              <td
                class="py-2 tracking-wide border-0"
                :style="{ backgroundColor: code.color }"
              >
                <label :for="code.id" class="cursor-pointer select-none">{{
                  code.name
                }}</label>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted, ref, defineAsyncComponent, markRaw } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '../Components/interactive/Button.vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/solid';
import { createCSV } from '../files/createCSV.js';
import { saveTextFile } from '../files/saveTextFile.js';
import { debounce } from '../utils/debounce.js';
import { unfoldCodes } from './analysis/unfoldCodes.js';
import Headline2 from '../Components/layout/Headline2.vue';
import { createByPropertySorter } from '../utils/sortByProperty.js';

// TODO: https://www.npmjs.com/package/html-to-rtf
const hasSelections = ref(false);
const props = defineProps(['sources', 'codes', 'codeBooks']);

const byName = createByPropertySorter('name');

const files = ref([]);
const allCodes = ref([]);
const checkedFiles = ref(new Map());
const checkedCodes = ref(new Map());
const selection = ref([]);

const getAllFiles = () => props.sources;
const getAllCodes = () => allCodes.value;

const eachCheckedFiles = (callback) => {
  for (const file of files.value) {
    if (checkedFiles.value.get(file.id)) {
      callback();
    }
  }
};

const eachCheckedCodes = (callback) => {
  for (const code of allCodes.value) {
    if (checkedCodes.value.get(code.id)) {
      callback(code);
    }
  }
};

const getAllSelections = () => {
  const out = [];
  eachCheckedFiles((file) => {
    eachCheckedCodes((code) => {
      for (const selection of code.text) {
        if (file.id === selection.source_id) {
          out.push(selection);
        }
      }
    });
  });
  return out;
};

const getCodesForFile = (file) => {
  return allCodes.value.filter(
    (code) =>
      !!checkedCodes.value.get(code.id) &&
      code.text.some((t) => t.source_id === file.id)
  );
};

// TODO move to external module, make functions "pure" and injectable etc.
const VisualizationPluginAPI = {
  getAllFiles,
  getAllCodes,
  eachCheckedFiles,
  eachCheckedCodes,
  getAllSelections,
  getCodesForFile,
};

let visualizerComponent = ref(null);
const plugins = {
  list: {
    title: 'List',
    load: () => import('./analysis/ListView.vue'),
  },
  portrait: {
    title: 'Code Portrait',
    load: () => import('./analysis/CodePortrait.vue'),
  },
  cloud: {
    title: 'Word Cloud',
    load: () => import('./analysis/WordCloudView.vue'),
  },
};
const availablePlugins = ref(
  Object.entries(plugins).map(([name, val]) => {
    return { name, title: val.title };
  })
);
const selectVisualizerPlugin = ({ value }) => {
  const loader = plugins[value].load;
  visualizerComponent.value =
    loader === null ? loader : markRaw(defineAsyncComponent(loader));
};

const checkFile = (event, id) => {
  const isAllFiles = id === 'all_files';
  const isChecked = !!checkedFiles.value.get(id);

  if (isAllFiles) {
    getAllFiles().forEach((file) => {
      checkedFiles.value.set(file.id, !isChecked);
    });
  } else {
    checkedFiles.value.set('all_files', false);
  }

  checkedFiles.value.set(id, !isChecked);
  updateHasSelection();
};
const checkCode = (event, id) => {
  const isAllCodes = id === 'all_codes';
  const isChecked = !!checkedCodes.value.get(id);

  if (isAllCodes) {
    getAllCodes().forEach((code) => {
      checkedCodes.value.set(code.id, !isChecked);
    });
  } else {
    checkedCodes.value.set('all_codes', false);
  }

  checkedCodes.value.set(id, !isChecked);
  updateHasSelection();
};

const updateHasSelection = debounce(() => {
  // TODO debounce or throttle?
  const values = [];

  props.sources.forEach((file) => {
    if (!checkedFiles.value.get(file.id)) {
      return;
    }

    const entry = {
      name: file.name,
      codes: [],
    };

    const iterateCodes = (list) => {
      list.forEach((code) => {
        if (checkedCodes.value.get(code.id) && code.text) {
          const current = {
            name: code.name,
            segments: [],
          };

          code.text.forEach((t) => {
            if (t.source_id === file.id) {
              current.segments.push(t);
            }
          });

          if (current.segments.length > 0) {
            entry.codes.push(current);
          }
        }

        if (code.children) {
          iterateCodes(code.children);
        }
      });
    };

    iterateCodes(allCodes);

    if (entry.codes.length > 0) {
      values.push(entry);
    }
  });

  selection.value = values;
  hasSelections.value = values.length > 0;
}, 500);

const saveCSV = () => {
  const doubleQuote = /"/g;
  const quote = "'";
  const whitespace = /\s+/g;
  const csv = createCSV({
    header: [
      'file',
      'code category',
      'created by',
      'created at',
      'last update',
      'start pos',
      'end pos',
      'selection',
    ],
  });
  selection.value.forEach((entry) => {
    entry.codes.forEach((code) => {
      code.segments.forEach((segment) => {
        csv.addRow([
          entry.name,
          code.name,
          segment.createdBy,
          segment.createdAt,
          segment.updatedAt !== segment.createdAt ? segment.updatedAt : '',
          segment.start,
          segment.end,
          `"${segment.text.replace(doubleQuote, quote).replace(whitespace, ' ')}"`,
        ]);
      });
    });
  });

  const out = csv.build();
  const date = new Date().toLocaleDateString().replace(/[_.:,\s]+/g, '-');

  saveTextFile({
    text: out,
    name: `codes-${date}.csv`,
    type: 'text/csv',
  });
};

onMounted(async () => {
  allCodes.value = unfoldCodes(props.codes).sort(byName);
  selectVisualizerPlugin({ value: 'list' });
  files.value = props.sources;
  files.value.sort(byName);
});
</script>
