<script setup>
defineProps({
  title: String,
  label: String,
  class: String,
  icon: Object,
});
</script>

<template>
  <button
    :title="title"
    type="button"
    :class="[
      'group/pbutton rounded-xl px-3 py-1 flex items-center bg-transparent border border-outline-l hover:bg-background-l',
      $props.class,
    ]"
  >
    <component
      v-if="icon"
      :is="icon"
      class="text-label-l group-hover/pbutton:text-label-lhover w-4 h-4"
    />
    <span class="text-label-l group-hover/pbutton:text-label-lhover"
      >{{ label }}<slot
    /></span>
  </button>
</template>
