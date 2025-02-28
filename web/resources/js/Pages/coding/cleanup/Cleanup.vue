<script setup lang="ts">
import { useCleanup } from './useCleanup';
import { computed } from 'vue';
import CleanupList from './CleanupList.vue';

const Cleanup = useCleanup();
const { entries } = Cleanup;
const selections = computed(() =>
  Object.values(entries.value).filter((entry) => entry.type === 'selection')
);
const codes = computed(() =>
  Object.values(entries.value).filter((entry) => entry.type === 'code')
);
const codebooks = computed(() =>
  Object.values(entries.value).filter((entry) => entry.type === 'codebook')
);
</script>

<template>
  <CleanupList
    v-if="selections?.length"
    title="Selections to clean"
    :entries="selections"
  />
  <CleanupList v-if="codes?.length" title="Codes to clean" :entries="codes" />
  <CleanupList
    v-if="codebooks?.length"
    title="Codebooks to clean"
    :entries="codebook"
  />
</template>

<style scoped></style>
