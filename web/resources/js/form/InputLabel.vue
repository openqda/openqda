<script setup>
import { cn } from '../utils/css/cn.js'

defineProps({
  value: String,
  class: String,
});
</script>

<template>
  <label
    :class="cn('block text-left text-sm group-focus:text-xs font-normal leading-6 tracking-wider text-foreground/60', $props.class)">
    <span v-if="value">{{ value }}</span>
    <span v-else><slot /></span>
  </label>
</template>
