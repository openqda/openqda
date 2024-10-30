<script setup lang="ts">
import { ChevronRightIcon, CheckIcon } from '@heroicons/vue/24/solid/index.js';
import { computed, ref } from 'vue';
import { cn } from '../../../utils/css/cn';
import Button from '../../../Components/interactive/Button.vue';
import { changeRGBOpacity } from '../../../utils/color/changeRGBOpacity'
import { useSelections } from '../selections/useSelections'

const { select } = useSelections();
const props = defineProps({
  code: Object,
  parent: Object,
  liClass: String,
});

const children = computed(
  () => props.code && props.code.children.filter((child) => child.active)
);
const open = ref(false);


</script>

<template>
  <li
    :class="
      cn(
        'p-0 my-2 text-sm rounded-md selection-none contextMenuOption flex items-center border-4',
        props.liClass
      )
    "
  >
    <button
      v-if="code.children.length"
      title="Toggle children"
      class="p-0 my-2 me-2 bg-transparent"
      @click.prevent="open = !open"
    >
      <ChevronRightIcon :class="cn('w-4 h-4', open && 'rotate-90')" />
    </button>
    <span class="w-4 h-4 my-2 me-2" v-else></span>
    <button
      class="border-4 w-full p-2 rounded-md"
      :style="{
        borderColor: changeRGBOpacity(code.color, 1),
      }"
      @click="select({ code, parent })"
    >
      {{ code.name }}
    </button>
  </li>

  <ul v-if="code.children?.length && open">
    <CodingContextMenuItem
      v-for="child in children"
      :key="child.id"
      :code="child"
      :parent="code"
      li-class="ps-3"
    />
  </ul>
</template>

<style scoped></style>
