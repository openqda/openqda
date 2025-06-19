<script setup>
defineProps({
  type: {
    type: String,
    default: 'button',
  },
  class: String,
});
</script>

<template>
  <button
    v-bind="$props"
    :type="type"
    :class="[
      'px-4 py-2 bg-transparent border-primary-l  rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-hidden focus:ring-2 focus:ring-cerulean-700 focus:ring-offset-2 transition ease-in-out duration-150',
      $props.class,
    ]"
  >
    <slot />
  </button>
</template>
