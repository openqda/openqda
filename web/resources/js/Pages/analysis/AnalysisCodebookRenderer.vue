<script setup lang="ts">
import Headline3 from '../../Components/layout/Headline3.vue';
import { computed } from 'vue';

const props = defineProps({
  codebook: Object,
  codes: Array,
});

const codesCount = computed(() => {
  const countCodes = (codes) => {
    let count = 0;
    codes.forEach((code) => {
      count += 1;
      if (code.children?.length) {
        count += countCodes(code.children);
      }
    });
    return count;
  };
  return countCodes(props.codes);
});

</script>

<template>
  <div class="flex justify-between items-center">
    <Headline3>{{ codebook.name }}</Headline3>
    <!-- codebook options -->
    <span class="flex justify-between items-center gap-2">
      <span class="text-foreground/50 text-xs mx-2">
        <span>{{ codesCount ?? 0 }} codes</span>
      </span>
    </span>
  </div>
</template>

<style scoped></style>
