<script setup lang="ts">
import { ChevronRightIcon, CheckIcon } from '@heroicons/vue/24/solid/index.js';
import { computed, ref } from 'vue';
import { cn } from '../../../utils/css/cn';
import Button from '../../../Components/interactive/Button.vue';
import { changeRGBOpacity } from '../../../utils/color/changeRGBOpacity'
import { useSelections } from '../selections/useSelections'
import {useContextMenu} from "./useContextMenu";

const { close } = useContextMenu();
const { select, reassignCode } = useSelections();
const props = defineProps({
  code: Object,
  parent: Object,
  reassign: Object,
  liClass: String,
});

const children = computed(
  () => props.code && props.code.children.filter((child) => child.active)
);
const open = ref(false);
const handle = async ({ code, parent }) => {
    if (props.reassign) {
        await reassignCode({  selection: props.reassign, code })
    }
    else {
        await select({ code, parent })
    }

    close()
}
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
      v-if="code.children.length"
      title="Toggle children"
      class="p-0 my-2 me-2 bg-transparent text-foreground"
      @click.prevent="open = !open"
    >
      <ChevronRightIcon :class="cn('w-4 h-4', open && 'rotate-90')" />
    </button>
    <span class="w-4 h-4 my-2 me-2" v-else></span>
    <button
      class="w-full p-2 rounded-md text-left line-clamp-1 hover:font-semibold"
      :style="{
        background: changeRGBOpacity(code.color, 1),
      }"
      @click="handle({ code, parent })"
    >
      {{ code.name }}
    </button>
  </li>

  <ul v-if="code.children?.length && open">
    <CodingContextMenuItem
      v-for="child in children"
      :reassign="props.reassign"
      :key="child.id"
      :code="child"
      :parent="code"
      li-class="ps-3"
    />
  </ul>
</template>

<style scoped></style>
