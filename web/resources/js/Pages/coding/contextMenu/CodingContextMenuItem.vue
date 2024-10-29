<script setup lang="ts">
import { useContextMenu } from './useContextMenu';

const { select } = useContextMenu();
defineProps({
  code: Object,
  parent: Object
});

const changeRGBOpacity = (rgba, opacity) => {
  const rgbaValues = rgba.match(/[\d.]+/g);
  if (rgbaValues && rgbaValues.length >= 3) {
    return `rgba(${rgbaValues[0]}, ${rgbaValues[1]}, ${rgbaValues[2]}, ${opacity})`;
  }
  return rgba;
};
</script>

<template>
  <li
    class="px-4 py-2 my-2 group text-sm rounded-md cursor-pointer hover:bg-white selection-none contextMenuOption"
    :style="{
      backgroundColor: changeRGBOpacity(code.color, 1),
    }"
    @click="select({ code, parent })"
  >
    <span class="group-hover:bg-surface group-hover:text-foreground px-1">
      {{ code.name }}
    </span>
  </li>

  <ul v-if="code.children?.length">
    <CodingContextMenuItem
      v-for="child in code.children"
      :key="child.id"
      :code="child"
      :parent="code"
    />
  </ul>
</template>

<style scoped></style>
