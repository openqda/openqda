<template>
  <button
    type="button"
    :disabled="disabled ? 'disabled' : false"
    :class="`rounded bg-${color}-700 px-2 py-1 text-xs font-semibold text-white shadow-sm hover:bg-${color}-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-${color}-500 inline-flex items-center ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`"
  >
    <component
      :is="icon"
      :class="`h-${iconSize || 4} w-${iconSize || 4} text-white mr-1`"
    ></component>
    <span>{{ label }}</span>
  </button>
</template>

<script setup>
defineProps(['label', 'color', 'icon', 'disabled', 'iconSize']);
</script>
