<script setup lang="ts">
import { computed, ref } from 'vue';
import { useCodeTree } from '../coding/tree/useCodeTree';
import { BarsArrowDownIcon, ChevronRightIcon } from '@heroicons/vue/24/solid';
import Button from '../../Components/interactive/Button.vue';
import { cn } from '../../utils/css/cn';
import ContrastText from '../../Components/text/ContrastText.vue';
import { changeOpacity } from '../../utils/color/changeOpacity';
import { useAnalysis  } from './useAnalysis';
const { collapsed, toggleCollapse } = useCodeTree();
const props = defineProps({
  code: Object,
  class: String,
  sorting: Boolean,
  showDetails: Boolean,
});

//------------------------------------------------------------------------
// Collapse
//------------------------------------------------------------------------
const open = computed(() => collapsed.value[props.code.id]);
const toggle = () => {
  toggleCollapse(props.code.id);
};

//------------------------------------------------------------------------
// TEXTS (SELECTIONS)
//------------------------------------------------------------------------
const showTexts = ref(false);
const textSelectionsCount = (code) => {
  let count = code?.text?.length ?? 0;
  if (code.children?.length) {
    code.children.forEach((child) => {
      count += textSelectionsCount(child);
    });
  }

  return count;
};
const textCount = computed(() => {
  return textSelectionsCount(props.code);
});

const textNotesCount = (code) => {
  let count = code?.notes?.length ?? 0;
  if (code.children?.length) {
    code.children.forEach((child) => {
      count += textNotesCount(child);
    });
  }

  return count;
};
const notesCount = computed(() => {
  return textNotesCount(props.code);
});
</script>

<template>
  <div class="w-full">
    <div class="flex items-center w-auto">
      <!-- collapse button -->
      <Button
        v-if="code.children?.length"
        :title="open ? 'Hide children' : 'Show children'"
        variant="default"
        size="sm"
        class="bg-transparent text-foreground! hover:text-background w-4 p-0! rounded"
        @click="toggle()"
      >
        <ChevronRightIcon
          :class="
            cn(
              'w-3 h-3 transition-all duration-300 transform',
              open && 'rotate-90'
            )
          "
        />
      </Button>
      <span class="w-4 h-4" v-else></span>

      <!-- code name -->
      <div
        :class="
          cn(
            'w-full tracking-wide rounded-md px-2 py-1 text-sm text-foreground dark:text-background group hover:shadow',
            sorting && 'cursor-grab'
          )
        "
        :style="`background: ${changeOpacity(code.color ?? 'rgba(0,0,0,1)', 1)};`"
      >
        <button
          v-if="!sorting && range?.length"
          @click.prevent="Selections.select({ code })"
          :title="`Assign ${code.name} to selection ${range.start}:${range.end}`"
          :class="
            cn(
              'w-full h-full text-left flex items-center',
              !code.active && 'cursor-not-allowed text-foreground/20'
            )
          "
        >
          <ContrastText
            :class="
              cn(
                code.active && 'hover:text-primary-foreground hover:bg-primary'
              )
            "
            >{{ code.name }}</ContrastText
          >
        </button>
        <ContrastText v-else>{{ code.name }}</ContrastText>
        <ContrastText
          v-if="props.showDetails && code.description"
          class="block my-1 text-xs"
          >Description: {{ code.description }}</ContrastText
        >
        <!-- input -->
        <div class="flex justify-end items-center gap-2">
          <span class="flex gap-1"><BarsArrowDownIcon class="w-4 h-4" /> {{ textCount }}</span>
          <input
            :id="code.id"
            type="checkbox"
            class="cursor-pointer"
          />
        </div>
      </div>
      </div>
  </div>
</template>

<style scoped></style>
