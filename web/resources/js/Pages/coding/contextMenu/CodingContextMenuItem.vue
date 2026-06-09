<script setup lang="ts">
import { ChevronRightIcon } from '@heroicons/vue/24/solid/index.js';
import { ref } from 'vue';
import { cn } from '../../../utils/css/cn';
import { changeOpacity } from '../../../utils/color/changeOpacity';
import { useSelections } from '../selections/useSelections';
import { useContextMenu } from './useContextMenu';

const { close } = useContextMenu();
const { select, reassignCode } = useSelections();
const props = defineProps({
  code: Object,
  parent: Object,
  reassign: Object,
  liClass: String,
  children: Array,
  query: String,
});

const open = ref(false);
const handle = async ({ code, parent }) => {
  if (props.reassign) {
    await reassignCode({ selection: props.reassign, code });
  } else {
    await select({ code, parent });
  }

  close();
};
</script>

<template>
  <li
    :class="
      cn(
        'p-0 my-2 text-sm rounded-md selection-none contextMenuOption flex items-center text-foreground dark:text-background',
        props.liClass
      )
    "
  >
    <button
      v-if="children.length"
      title="Toggle children"
      class="p-0 my-2 me-2 bg-transparent text-foreground"
      @click.prevent="open = !open"
    >
      <ChevronRightIcon
        :class="cn('w-4 h-4', open || (query?.length && 'rotate-90'))"
      />
    </button>
    <span class="w-4 h-4 my-2 me-2" v-else></span>
    <button
      class="w-full p-2 rounded-md text-left line-clamp-1 hover:font-semibold"
      :style="{
        background: changeOpacity(code.color, 1),
      }"
      @click="handle({ code, parent })"
    >
      {{ code.name }}
    </button>
  </li>

  <ul v-if="children?.length && (open || query?.length)" class="ps-3">
    <CodingContextMenuItem
      v-for="entry in children"
      :reassign="props.reassign"
      :key="entry.code?.id"
      :code="entry.code"
      :children="entry.children"
      :parent="code"
      :query="query"
      li-class="ps-3"
    />
  </ul>
</template>

<style scoped></style>
