<script setup>
import { onMounted, ref } from 'vue';
import InputError from './InputError.vue';
import InputLabel from './InputLabel.vue';
import { cn } from '../utils/css/cn.js'

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
    :class="cn(
      'input-field peer mt-1 block w-full bg-transparent border-2 border-foreground/10',
      'focus:outline-0 focus:ring-0 rounded-none focus:border-foreground/80',
      'text-foreground placeholder-foreground active:text-foreground',
      $props.class,
    )"
    v-model="value"
  ></textarea>
  <InputError v-if="props.isValid === false" :message="props.error" />
</template>
