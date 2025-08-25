<script setup lang="ts">
import { provide } from 'vue';
import { createVisualizationAPI } from './createVisualizationAPI';
import { useAnalysis } from '../useAnalysis';
import { useVisualizerPlugins } from './useVisualizerPlugins';
import { useUsers } from '../../../domain/teams/useUsers';
import Headline3 from '../../../Components/layout/Headline3.vue';
import Button from '../../../Components/interactive/Button.vue';
import SideOverlay from "../../../Components/layout/SideOverlay.vue";

const {
  sources,
  codes,
  checkedSources,
  checkedCodes,
  hasSelections,
  checkSource,
} = useAnalysis();
const { getMemberBy } = useUsers();
const { visualizerComponent, showMenu, setShowMenu } = useVisualizerPlugins();
const { api } = createVisualizationAPI({
  sources,
  codes,
  checkedCodes,
  checkedSources,
});

// additional functionality attached
api.getMemberBy = getMemberBy;
api.setShowMenu = setShowMenu;

provide('api', api);
provide('components', {
  Headline3,
  Button,
});
</script>

<template>
  <component
    :is="visualizerComponent"
    :codes="codes"
    :api="api"
    :sources="sources"
    :hasSelections="hasSelections"
    :checkedCodes="checkedCodes"
    :menu="SideOverlay"
    :showMenu="showMenu"
    :checkedSources="checkedSources"
    @remove="(id) => checkSource(id)"
  >
  </component>
</template>

<style scoped></style>
