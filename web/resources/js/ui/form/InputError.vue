<script setup>
defineProps({
  message: String,
  class: String,
});
</script>

<template>
  <p
    v-show="message"
    :class="[
      'text-sm text-danger-l dark:text-danger-d text-left',
      $props.class,
    ]"
  >
    {{ message }}
  </p>
</template>
