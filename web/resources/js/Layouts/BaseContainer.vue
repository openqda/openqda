<script setup lang="ts">
import { cn } from '../utils/css/cn';
defineProps(['class']);
</script>

<template>
  <div
    :class="
      cn(
        ' w-full h-full p-1 sm:p-4 lg:p-5 flex flex-col gap-1 md:gap-3 lg:gap-6',
        $props.class
      )
    "
  >
    <slot></slot>
  </div>
</template>

<style scoped></style>
