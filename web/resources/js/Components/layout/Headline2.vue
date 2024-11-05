<template>
  <h2 :class="['font-semibold leading-6 text-lg text-foreground', props.class]">
    <slot></slot>
  </h2>
</template>

<script setup>
const props = defineProps(['class']);
</script>
