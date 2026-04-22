<script setup>
import { onMounted, ref } from 'vue';
import InputError from './InputError.vue';
import InputLabel from './InputLabel.vue';
import { cn } from '../utils/css/cn.js';

const props = defineProps({
  modelValue: String,
  label: String,
  placeholder: String,
  class: String,
  rows: {
    type: Number,
    default: 3,
  },
  validation: Object,
  value: String,
  defaultValue: String,
  name: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);
const errors = ref([]);
const value = ref(props.value ?? props.defaultValue ?? '');
onMounted(() => {
  if (input.value.hasAttribute('autofocus')) {
    input.value.focus();
  }
  errors.value =
    props.validation?.valid === false && props.validation?.errors?.length
      ? props.validation.errors
      : [];
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
    :class="
      cn(
        'input-field peer mt-1 block w-full bg-transparent border-2 border-foreground/10',
        'focus:outline-0 focus:ring-0 rounded-none focus:border-foreground/80',
        'text-foreground placeholder-foreground/50 active:text-foreground',
        $props.class,
        props.validation?.valid === false && 'border-destructive'
      )
    "
    v-model="value"
  ></textarea>
  <InputError v-for="error in errors" :key="error" :message="error" />
</template>
