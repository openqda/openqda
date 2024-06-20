<template>
  <button
    type="button"
    :disabled="disabled ? 'disabled' : false"
    :class="[
      'rounded font-semibold shadow-sm inline-flex items-center',
      'px-2 py-1',
      `bg-${col}-700 text-xs  text-white hover:bg-${col}-500  ${disabled === true ? 'opacity-50 cursor-not-allowed' : ''}`,
      `focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-${col}-500`,
    ]"
  >
    <component
      :is="icon"
      :class="`h-${iconSize || 4} w-${iconSize || 4} text-white mr-1`"
    ></component>
    <span>{{ label }}</span>
  </button>
</template>

<script setup>
import { ref } from 'vue';

defineProps({
  type: {
    type: String,
    required: false,
  },
  /**
   * The actual button label to display
   */
  label: {
    type: String,
    required: false,
  },
  /**
   * main background color of the button
   */
  color: {
    type: String,
    required: false,
  },
  icon: {
    type: Object,
    required: false,
  },
  iconSize: {
    type: Number,
    required: false,
  },
  disabled: {
    type: Boolean,
    required: false,
  },
});

const col = ref('cerulean');
</script>
