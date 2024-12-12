<script setup lang="ts">
import { cn } from '../../utils/css/cn.js';
defineProps(['class', 'orientation']);
</script>

<template>
  <div
    role="separator"
    :class="
      cn(
        'shrink-0 bg-border',
        orientation === 'horizontal' ? 'h-[1px] w-full' : 'h-full w-[1px]'
      )
    "
    :aria-orientation="orientation === 'vertical' ? orientation : undefined"
    :data-orientation="orientation ?? 'horizontal'"
  ></div>
</template>
