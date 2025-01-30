<script setup lang="ts">
import { cn } from '../utils/css/cn';
defineProps(['class']);
</script>

<template>
  <div
    :class="
      cn(
        ' w-full h-full p-1 sm:p-4 lg:p-5 space-y-1 md:space-y-3 lg:space-y-6',
        $props.class
      )
    "
  >
    <slot></slot>
  </div>
</template>

<style scoped></style>
