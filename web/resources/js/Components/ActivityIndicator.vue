<script setup lang="ts">
import { ArrowPathIcon } from '@heroicons/vue/24/solid';
import { cn } from '../utils/css/cn';
</script>

<template>
  <div
    :class="cn('flex items-center text-sm text-foreground/80', $props.class)"
  >
    <ArrowPathIcon class="w-4 h-4 animate-spin" />
    <slot v-if="$slots.default" />
    <span v-else>Loading...</span>
  </div>
</template>
