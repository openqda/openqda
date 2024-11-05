<script setup>
import { cn } from '../utils/css/cn.js';

const props = defineProps({
  on: Boolean,
  class: { type: String, required: false },
});
</script>

<template>
  <div>
    <transition
      leave-active-class="transition ease-in duration-1000"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-show="on" :class="cn('text-sm text-foreground', props.class)">
        <slot />
      </div>
    </transition>
  </div>
</template>
