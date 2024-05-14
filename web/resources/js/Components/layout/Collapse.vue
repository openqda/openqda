<template>
  <Transition name="collapse">
    <slot></slot>
  </Transition>
</template>

<script setup>
// created with https://vuejs.org/guide/built-ins/transition.html#reusable-transitions
const props = defineProps(['open'])
</script>

<style>
/*
  Enter and leave animations can use different
  durations and timing functions.
*/
.collapse-enter-active {
  transition:
    opacity 0.1s,
    font-size 0.2s,
    margin 0.2s,
    padding 0.2s,
    max-height 0.2s ease-out;
}

.collapse-leave-active {
  transition:
    opacity 0.1s,
    font-size 0.2s,
    margin 0.2s,
    padding 0.2s,
    max-height 0.2s ease-out;
}

.collapse-enter-from,
.collapse-leave-to {
  font-size: 0;
  max-height: 0;
  margin: 0;
  opacity: 0;
  padding: 0;
}
</style>
