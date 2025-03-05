<script setup lang="ts">
import { provide } from 'vue';
import AutoForm from '../../../form/AutoForm.vue';
import { createVisualizationAPI } from './createVisualizationAPI';
import { useAnalysis } from '../useAnalysis';
import { useVisualizerPlugins } from './useVisualizerPlugins';
import { useUsers } from '../../../domain/teams/useUsers';
import Headline3 from '../../../Components/layout/Headline3.vue';
import Button from '../../../Components/interactive/Button.vue';

const {
  sources,
  codes,
  checkedSources,
  checkedCodes,
  hasSelections,
  checkSource,
} = useAnalysis();
const { getMemberBy } = useUsers();
const { visualizerComponent } = useVisualizerPlugins();
const { api, optionsSchema } = createVisualizationAPI({
  sources,
  codes,
  checkedCodes,
  checkedSources,
});

// additional functionality attached
api.getMemberBy = getMemberBy;
provide('api', api);
provide('components', {
  Headline3,
  Button,
});
</script>

<template>
  <div class="flex">
    <AutoForm
      v-if="optionsSchema"
      :schema="optionsSchema"
      class="flex w-full items-center"
      :show-cancel="false"
      :show-submit="false"
    />
  </div>
  <component
    :is="visualizerComponent"
    :codes="codes"
    :api="api"
    :sources="sources"
    :hasSelections="hasSelections"
    :checkedCodes="checkedCodes"
    :checkedSources="checkedSources"
    @remove="(id) => checkSource(id)"
  />
</template>

<style scoped></style>
