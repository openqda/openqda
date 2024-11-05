<template>
  <h3 :class="cn('font-semibold leading-6 text-lg text-primary', props.class)">
    <slot></slot>
  </h3>
</template>
<script setup>
import { cn } from '../../utils/css/cn.js';

const props = defineProps(['class']);
</script>
