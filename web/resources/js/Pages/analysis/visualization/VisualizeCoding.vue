<script setup lang="ts">
import AutoForm from '../../../form/AutoForm.vue';
import { createVisualizationAPI } from './createVisualizationAPI';
import { useAnalysis } from '../useAnalysis';
import { useVisualizerPlugins } from './useVisualizerPlugins';
import {useUsers} from "../../../domain/teams/useUsers";

const {
  sources,
  codes,
  checkedSources,
  checkedCodes,
  hasSelections,
  checkSource,
} = useAnalysis();
const {getMemberBy} = useUsers()
const { visualizerComponent } = useVisualizerPlugins();
const { api, optionsSchema } = createVisualizationAPI({
  sources,
  codes,
  checkedCodes,
  checkedSources,
});

// additional functionality attached
api.getMemberBy = getMemberBy
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
    :api="api"
    :codes="codes"
    :sources="sources"
    :hasSelections="hasSelections"
    :checkedCodes="checkedCodes"
    :checkedSources="checkedSources"
    @remove="(id) => checkSource(id)"
  />
</template>

<style scoped></style>
