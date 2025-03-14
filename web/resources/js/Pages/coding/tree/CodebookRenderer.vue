<script setup lang="ts">
import {
  ArrowPathIcon,
  ArrowsUpDownIcon,
  EyeIcon,
  EyeSlashIcon,
} from '@heroicons/vue/24/solid';
import Headline3 from '../../../Components/layout/Headline3.vue';
import { computed, reactive } from 'vue';
import { asyncTimeout } from '../../../utils/asyncTimeout';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { useCodes } from '../../../domain/codes/useCodes';
import { useCodeTree } from './useCodeTree';
import { cn } from '../../../utils/css/cn';

const props = defineProps({
  codebook: Object,
  codes: Array,
  canSort: Boolean,
  canToggle: Boolean,
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

//------------------------------------------------------------------------
// SORTING
//------------------------------------------------------------------------
const { sorting, setSorting } = useCodeTree();

//------------------------------------------------------------------------
// TOGGLE
//------------------------------------------------------------------------
const { toggleCodebook } = useCodes();
const toggling = reactive({});
const handleTogglingCodebook = async (codebook) => {
  toggling[codebook.id] = true;
  await asyncTimeout(300);
  await attemptAsync(() => toggleCodebook(codebook));
  toggling[codebook.id] = false;
};
</script>

<template>
  <div class="flex justify-between items-center">
    <Headline3>{{ codebook.name }}</Headline3>
    <!-- codebook options -->
    <span class="flex justify-between items-center gap-2">
      <span class="text-foreground/50 text-xs mx-2">
        <span>{{ codesCount ?? 0 }} codes</span>
        <span v-if="sorting === codebook.id">, sorting mode</span>
      </span>
      <button
        v-if="props.canToggle != false"
        class="p-0 m-0 text-foreground/80"
        @click="handleTogglingCodebook(codebook)"
        :title="codebook.active ? 'Hide all codes' : 'Show all codes'"
      >
        <ArrowPathIcon
          v-if="toggling[codebook.id]"
          class="w-4 h-4 animate-spin text-foreground/50"
        />
        <EyeSlashIcon
          v-else-if="codebook.active === false"
          class="w-4 h-4 text-foreground/50"
        />
        <EyeIcon v-else class="w-4 h-4" />
      </button>
      <button
        v-if="props.canSort != false"
        :class="
          cn(
            'p-0 m-0',
            sorting === codebook.id ? 'text-secondary' : 'text-foreground'
          )
        "
        @click="setSorting(sorting === codebook.id ? null : codebook.id)"
        :title="
          sorting === codebook.id
            ? 'Deactivate code-sorting'
            : 'Activate code-sorting'
        "
      >
        <ArrowsUpDownIcon class="w-4 h-4" />
      </button>
    </span>
  </div>
</template>

<style scoped></style>
