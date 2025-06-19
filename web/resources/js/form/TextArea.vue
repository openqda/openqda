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
  value: String,
  defaultValue: String,
  name: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);
const value = ref(props.value ?? props.defaultValue ?? '');
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
    :name="name"
    :placeholder="placeholder"
    :rows="rows"
    :class="[
      'input-field peer mt-1 block w-full bg-transparent border-outline-l/50 dark:border-outline-d/50',
      'placeholder-foreground/40 focus:outline-hidden focus:ring-0 focus:border-2 focus:border-outline-l dark:focus:border-outline-d rounded-none',
      'text-foreground',
      $props.class,
    ]"
    v-model="value"
  ></textarea>
  <InputError v-if="props.isValid === false" :message="props.error" />
</template>
