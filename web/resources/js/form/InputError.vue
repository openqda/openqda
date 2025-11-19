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
      'text-sm text-left bg-destructive text-destructive-foreground px-1',
      $props.class,
    ]"
  >
    {{ message }}
  </p>
</template>
