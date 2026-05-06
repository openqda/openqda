<template>
  <input
    ref="input"
    :value="modelValue"
    :type="type"
    :name="name"
    :class="
      cn(
        'peer input-field block w-full bg-transparent border-0  border-b-2 border-b-foreground/10',
        'outline outline-0 px-0.5',
        'focus:outline-0 focus:ring-0 rounded-none focus:border-b-foreground/80',
        'text-foreground placeholder-foreground/50 active:text-foreground',
        resolveText({ size: $props.size }),
        $props.class
      )
    "
    :placeholder="$props.placeholder"
    :autocomplete="$props.autocomplete"
    v-bind="$attrs"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>
<script setup>
import { onMounted, ref } from 'vue';
import { cn } from '../utils/css/cn.js';
import { variantAuthority } from '../utils/css/variantAuthority.js';

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
  size: {
    type: String,
    default: 'default',
  },
});

onMounted(() => {
  if (input.value.hasAttribute('autofocus')) {
    input.value.focus();
  }
});

defineExpose({ focus: () => input.value.focus() });
const textStyle = {
  class: '',
  variants: {
    size: {
      xs: 'text-sm',
      default: 'text-base',
      sm: 'text-sm',
      md: 'text-base',
      lg: 'text-lg',
    },
  },
  defaultVariants: {
    size: 'default',
  },
};
const resolveText = variantAuthority(textStyle);
</script>

<style scoped>
@reference "../../css/app.css";
input {
  background-clip: text !important;
  -webkit-background-clip: text !important;
}

input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
  -webkit-text-fill-color: var(--color-foreground);
}
</style>
