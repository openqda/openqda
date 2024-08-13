<script setup>
import { onMounted, ref } from 'vue';

defineProps({
  modelValue: String,
  placeholder: String,
  rows: {
    type: Number,
    defauktValue: 3,
  },
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
  if (input.value.hasAttribute('autofocus')) {
    input.value.focus();
  }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
  <textarea
    ref="input"
    class="border-gray-300 focus:border-cerulean-700 focus:ring-cerulean-700 rounded-md shadow-sm"
    :placeholder="placeholder"
    :rows="rows"
    @input="$emit('update:modelValue', $event.target.value)"
  ></textarea>
</template>
