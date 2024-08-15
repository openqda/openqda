<template>
  <input
    ref="input"
    :value="modelValue"
    :type="type"
    :name="name"
    :class="[
      'peer input-field block w-full bg-transparent border-0 border-b border-b-outline-l/50 dark:border-b-outline-d/50',
      'outline outline-0',
      'focus:outline-0 focus:ring-0 focus:border-outline-l dark:focus:border-outline-d focus:border-l-0 focus:border-r-0 focus:border-t-0 focus:border-b-2 rounded-none',
      'text-label-l dark:text-label-d',
      $props.class,
    ]"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>
<script setup>
import { onMounted, ref } from 'vue';

const input = ref(null);

defineEmits(['update:modelValue']);
defineProps({
  modelValue: String,
  placeholder: String,
  type: {
    type: String,
    default: 'text',
  },
  label: String,
  class: String,
  name: String,
});

onMounted(() => {
  if (input.value.hasAttribute('autofocus')) {
    input.value.focus();
  }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<style scoped>
input {
  -webkit-background-clip: text !important;
}
</style>
