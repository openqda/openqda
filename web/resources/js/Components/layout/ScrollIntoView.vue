<script setup lang="ts">
/**
 * Defines a parent element that automatically scrolls children into view,
 * once mounted.
 * @component
 */
import { onMounted, useTemplateRef } from 'vue';

const scrollIntoView = useTemplateRef('scroll-into-view');
const props = defineProps({
  behavior: {
    type: String,
    required: false,
    default: 'auto',
  },
  halign: {
    type: String,
    required: false,
    default: 'nearest',
  },
  valign: {
    type: String,
    required: false,
    default: 'nearest',
  },
  active: {
    type: Boolean,
    required: false,
    default: true,
  },
});
onMounted(() => {
  const { active, ...options } = props;
  if (!active) {
    return;
  }
  try {
    scrollIntoView.value.scrollIntoView(options);
  } catch (e) {
    console.error(e);
  }
});
</script>

<template>
  <div ref="scroll-into-view" :class="$props.class">
    <slot />
  </div>
</template>

<style scoped></style>
