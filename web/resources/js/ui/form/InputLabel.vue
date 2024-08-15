<script setup>
defineProps({
  value: String,
  class: String,
});
</script>

<template>
  <label
    :class="[
      'block text-left text-sm group-focus:text-xs font-thin uppercase leading-6 tracking-wider text-label-l/75 dark:text-label-d/75',
      $props.class,
    ]"
  >
    <span v-if="value">{{ value }}</span>
    <span v-else><slot /></span>
  </label>
</template>
