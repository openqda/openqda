<script setup>
import { onMounted, ref } from 'vue';
import InputError from './InputError.vue';
import InputLabel from './InputLabel.vue';

const props = defineProps({
  modelValue: String,
  label: String,
  placeholder: String,
  class: String,
  rows: {
    type: Number,
    default: 3,
  },
  isValid: Boolean,
  error: String,
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
  <InputLabel :value="props.label" />
  <textarea
    ref="input"
    :placeholder="placeholder"
    :rows="rows"
    :class="[
      'input-field peer mt-1 block w-full bg-transparent border-outline-l/50 dark:border-outline-d/50',
      'placeholder-opacity-40 focus:outline-none focus:ring focus:ring-0 focus:border-2 focus:border-outline-l dark:focus:border-outline-d rounded-none',
      'text-label-l dark:text-label-d',
      $props.class,
    ]"
    @input="$emit('update:modelValue', $event.target.value)"
  ></textarea>
  <InputError v-if="props.isValid === false" :message="props.error" />
</template>
