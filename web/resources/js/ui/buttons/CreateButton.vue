<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline';
defineProps({
  title: String,
  label: String,
  class: String,
});
</script>

<template>
  <button
    :title="title"
    :class="[
      'group/pbutton rounded-xl px-3 py-1 flex items-center bg-transparent border border-secondary-l hover:bg-secondary-l',
      $props.class,
    ]"
  >
    <PlusIcon
      class="text-secondary-l group-hover/pbutton:text-label-lhover w-4 h-4"
    />
    <span class="text-secondary-l group-hover/pbutton:text-label-lhover">{{
      label
    }}</span>
  </button>
</template>
