<template>
  <AuthenticatedLayout :menu="true" :showFooter="false" :title="pageTitle">
    <template #menu>
      <BaseContainer>
        <div class="flex justify-end">
          <ResponsiveTabList
            :tabs="menuTabs"
            :initial="menuView"
            @change="(value) => (menuView = value)"
          />
        </div>

        <div v-if="menuView === 'export'">
          <Button @click="exportToCSV(selection)" :disabled="!hasSelections" :title="hasSelections ? 'Export to CSV' : 'Select at least one File and Code to export'">
            Export to CSV
          </Button>
        </div>

        <div v-show="menuView === 'sources'" class="space-y-4">
          <FilesList
            :focus-on-hover="false"
            :fields="{
              lock: false,
              file: true,
              type: true,
              date: false,
              user: false,
            }"
            :documents="sources"
            :fixed="false"
            @select="(doc) => checkSource(doc.id)"
          >
            <template #custom-head>
              <th class="w-6 text-right">
                <input
                  id="all_files"
                  type="checkbox"
                  :checked="checkedSources.get('all_files')"
                  @change="checkSource('all_files')"
                />
              </th>
            </template>
            <template v-slot:custom-cells="{ source }">
              <td class="text-right">
                <input
                  :id="source.id"
                  type="checkbox"
                  :checked="checkedSources.get(source.id)"
                  @change="checkSource(source.id)"
                  class="cursor-pointer"
                />
              </td>
            </template>
          </FilesList>
        </div>

        <div v-if="menuView === 'codes'">
          <table class="w-full border-collapse border-0">
            <thead>
              <tr class="border-0">
                <th
                  scope="col"
                  class="p-2 text-left text-xs font-medium uppercase tracking-wide text-silver-300 sm:pl-0"
                >
                </th>
                <th style="width: 3rem"></th>
              </tr>
            </thead>
            <tbody>
              <tr class="text-sm border-0 hover:bg-silver-300">
                  <td class="py-2 tracking-wide text-right">
                      <label for="all_codes">All Codes</label>
                  </td>
                <td class="py-2 text-center tracking-wide">
                  <input
                    id="all_codes"
                    type="checkbox"
                    :checked="checkedCodes.get('all_codes')"
                    @change="checkCode('all_codes')"
                  />
                </td>
              </tr>
              <tr
                v-for="code in codes"
                :key="code.id"
                class="text-sm border-0"
              >
                <td
                  class="tracking-wide border-0 rounded-md"
                >
                  <label :for="code.id" class="cursor-pointer select-none line-clamp-1 rounded-md p-2 my-2 flex"
                         :style="{ backgroundColor: code.color, opacity: checkedCodes.get(code.id) ? 1 : 0.3 }">
                      <span class="flex-grow">{{code.name }}</span>
                      <span class="flex items-center">
                          <BarsArrowDownIcon class="w-4 h-4 me-1" />
                          {{code.text.length}}
                      </span>
                  </label>
                </td>
                  <td class="py-2 text-center tracking-wide border-0">
                      <input
                          :id="code.id"
                          type="checkbox"
                          :checked="checkedCodes.get(code.id)"
                          @change="checkCode(code.id)"
                          class="cursor-pointer"
                      />
                  </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseContainer>
    </template>
    <template #main>
      <BaseContainer>
        <div class="flex justify-between items-center">
            <div class="flex-shrink">
            <SelectField
                v-if="contentView === 'visualize'"
                id="location"
                name="location"
                class="text-foreground"
                :options="availablePlugins"
                :value="visualizerName ?? 'list'"
                @change="selectVisualizerPlugin($event.target)"
            />
            </div>
          <ResponsiveTabList
            :tabs="contentTabs"
            :initial="contentView"
            @change="(value) => (contentView = value)"
          />
        </div>
        <div v-if="contentView === 'visualize'">
            <VisualizeCoding />
        </div>
      </BaseContainer>
    </template>
  </AuthenticatedLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import Button from '../Components/interactive/Button.vue';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue';
import BaseContainer from '../Layouts/BaseContainer.vue';
import ResponsiveTabList from '../Components/lists/ResponsiveTabList.vue';
import FilesList from '../Components/files/FilesList.vue';
import { useExport } from '../exchange/useExport.js';
import { trunc } from '../utils/string/trunc.js';
import { BarsArrowDownIcon } from '@heroicons/vue/24/solid/index.js'
import { useAnalysis } from './analysis/useAnalysis.js'
import VisualizeCoding from './analysis/visualization/VisualizeCoding.vue'
import { useVisualizerPlugins } from './analysis/visualization/useVisualizerPlugins.js'
import SelectField from '../form/SelectField.vue'
//------------------------------------------------------------------------
// DATA / PROPS
//------------------------------------------------------------------------
const props = defineProps(['sources', 'codes', 'codebooks', 'project']);

//------------------------------------------------------------------------
// VIEWS / TABS
//------------------------------------------------------------------------
const menuTabs = [
  { value: 'sources', label: 'Sources' },
  { value: 'codes', label: 'Codes' },
  { value: 'export', label: 'Export' },
];
const menuView = ref(menuTabs[0].value);
const contentTabs = [
  { value: 'visualize', label: 'Visualize' },
  { value: 'analyze', label: 'Analyze' },
];
const contentView = ref(contentTabs[0].value);

//------------------------------------------------------------------------
// PAGE
//------------------------------------------------------------------------
// TODO: https://www.npmjs.com/package/html-to-rtf
const pageTitle = ref(`Analysis - ${trunc(props.project.name, 50)}`);


//------------------------------------------------------------------------
// SOURCES AND CODES
//------------------------------------------------------------------------
const {
    codes,
    checkedCodes,
    checkCode,
    sources,
    checkedSources,
    checkSource,
    hasSelections
} = useAnalysis()


const { availablePlugins, visualizerName, selectVisualizerPlugin } = useVisualizerPlugins()


//------------------------------------------------------------------------
// EXPORTS
//------------------------------------------------------------------------
const { exportToCSV } = useExport();

onMounted(async () => {
    selectVisualizerPlugin({ value: 'list', unlessExists: true });
    if (checkedSources.value.size === 0) checkSource('all_files');
    if (checkedCodes.value.size === 0) checkCode('all_codes');
});
</script>
