<script setup lang="ts">
import { useCleanup } from './useCleanup';
import { ref, onMounted } from 'vue';
import CleanupList from './CleanupList.vue';

const Cleanup = useCleanup();
const selections = ref([]);
const codes = ref([]);
const codebooks = ref([]);

onMounted(() => {
  const found = Object.values(Cleanup.entries.value);
  selections.value.push(...found.filter((entry) => entry.type === 'selection'));
  codes.value.push(...found.filter((entry) => entry.type === 'code'));
  codebooks.value.push(...found.filter((entry) => entry.type === 'codebook'));
});
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
