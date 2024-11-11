<template>
  <AuthenticatedLayout :menu="true" :showFooter="false" :title="pageTitle">
      <template #menu>
          <BaseContainer>
          <div class="flex justify-end">
              <ResponsiveTabList
                  :tabs="views"
                  :initial="view"
                  @change="(value) => (view = value)"
              />
          </div>
              <div v-if="view === 'export'">
                <Button
                    @click="exportToCSV(selection)"
                    :disabled="!hasSelections"
                >
                    Export to CSV
                </Button>
              </div>

              <div v-show="view === 'visualize'" class="space-y-4">
                  <SelectField
                      id="location"
                      name="location"
                      class="text-foreground"
                      value="list"
                      :options="availablePlugins"
                      @change="selectVisualizerPlugin($event.target)">
                  </SelectField>

                  <FilesList
                      :fields="{ lock: false, file: true, type: true, date: false, user: false }"
                      :documents="files"
                      :fixed="false"
                  >
                      <template #custom-head>
                          <th class="w-6 text-right">
                              <input
                                  id="all_files"
                                  type="checkbox"
                                  :checked="checkedFiles.get('all_files')"
                                  @change="checkFile('all_files')"
                              >
                          </th>
                      </template>
                      <template #custom-cells v-slot="{ document, index }">
                          <td :data-index="index">
                              <input
                                  :id="document.id"
                                  type="checkbox"
                                  :checked="checkedFiles.get(document.id)"
                                  @change="checkFile(document.id)"
                                  class="cursor-pointer"
                              >
                          </td>
                      </template>
                  </FilesList>
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
                              @change="checkCode('all_codes')"
                          />
                      </td>
                      <td class="py-2 tracking-wide">
                          <label for="all_codes">All Codes</label>
                      </td>
                  </tr>
                  <tr
                      v-for="code in allCodes"
                      :key="code.id"
                      class="text-sm border-0 hover:bg-silver-300"
                  >
                      <td class="py-2 text-center tracking-wide border-0">
                          <input
                              :id="code.id"
                              type="checkbox"
                              :checked="checkedCodes.get(code.id)"
                              @change="checkCode(code.id)"
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
          </BaseContainer>
      </template>
      <template #main>
          <BaseContainer>
              <AutoForm
                  v-if="optionsSchema"
                  :schema="optionsSchema"
                  class="flex w-full items-center"
                  :show-cancel="false"
                  :show-submit="false"
              />
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
          </BaseContainer>
      </template>
  </AuthenticatedLayout>
</template>

<script setup>
import { onMounted, ref, defineAsyncComponent, markRaw } from 'vue';
import Button from '../Components/interactive/Button.vue';
import { debounce } from '../utils/dom/debounce.js';
import { unfoldCodes } from './analysis/unfoldCodes.js';
import { createByPropertySorter } from '../utils/array/createByPropertySorter.js';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue'
import SelectField from '../form/SelectField.vue'
import BaseContainer from '../Layouts/BaseContainer.vue'
import ResponsiveTabList from '../Components/lists/ResponsiveTabList.vue'
import AutoForm from '../form/AutoForm.vue'
import FilesList from '../Components/files/FilesList.vue'
import { useExport } from '../exchange/useExport.js'
import Checkbox from '../form/Checkbox.vue'
import { trunc } from '../utils/string/trunc.js'
import Index from './API/Index.vue'

//------------------------------------------------------------------------
// DATA / PROPS
//------------------------------------------------------------------------
const props = defineProps(['sources', 'codes', 'codeBooks', 'project']);
console.debug(props.project)
//------------------------------------------------------------------------
// VIEWS / TABS
//------------------------------------------------------------------------
const views = [
    { value: 'visualize', label: 'Visualize' },
    { value: 'analyze', label: 'Analyze' },
    { value: 'export', label: 'Export' }
]
const view = ref(views[0].value)

// TODO: https://www.npmjs.com/package/html-to-rtf
const hasSelections = ref(false);
const pageTitle = ref(`Analysis - ${trunc(props.project.name, 50)}`)
const byName = createByPropertySorter('name');

const files = ref(
    props.sources
        .filter(f => {
            return true
        })
        .map(source => {
            const isLocked = (source.variables ?? []).some(({ name, boolean_value }) => name === 'isLocked' && boolean_value === 1)
            const copy = { ...source }
            copy.date = new Date(source.updated_at).toLocaleDateString()
            copy.variables = { isLocked }
            copy.isConverting = false
            copy.failed = false
            copy.converted = true
            return copy
        })
        .toSorted(byName)
);
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

const optionsSchema = ref(null)
const defineOptions = (schema) => {
    optionsSchema.value = schema
}

// TODO move to external module, make functions "pure" and injectable etc.
const VisualizationPluginAPI = {
  getAllFiles,
  getAllCodes,
  eachCheckedFiles,
  eachCheckedCodes,
  getAllSelections,
  getCodesForFile,
  debounce,
  defineOptions
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
    return { value: name, label: val.title };
  })
);
const selectVisualizerPlugin = ({ value }) => {
  const loader = plugins[value].load;
  visualizerComponent.value =
    loader === null ? loader : markRaw(defineAsyncComponent(loader));
};

const checkFile = (id) => {
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
const checkCode = (id) => {
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

    iterateCodes(allCodes.value);

    if (entry.codes.length > 0) {
      values.push(entry);
    }
  });

  selection.value = values;
  hasSelections.value = values.length > 0;
}, 500);

//------------------------------------------------------------------------
// EXPORTS
//------------------------------------------------------------------------
const { exportToCSV } = useExport()

onMounted(async () => {
  allCodes.value = unfoldCodes(props.codes).sort(byName);
  selectVisualizerPlugin({ value: 'list' });
  checkFile('all_files')
  checkCode('all_codes')
});
</script>
