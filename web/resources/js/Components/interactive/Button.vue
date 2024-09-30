<template>
  <button
    :type="$props.type ?? 'button'"
    :disabled="disabled ? 'disabled' : false"
    :class="
      cn(resolve({ variant: $props.variant ?? 'default', size: $props.size ?? 'default' }), props.class)
    "
  >
    <component
      :is="icon"
      :class="`h-${iconSize || 4} w-${iconSize || 4} text-inherit mr-1`"
    ></component>
    <slot />
  </button>
</template>

<script setup>
import { ref, watch } from 'vue'
import { cn } from '../../utils/css/cn.js';
import { variantAuthority } from '../../utils/css/variantAuthority.js'

const style = {
  class: 'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
  variants: {
    variant: {
    default: 'bg-primary text-primary-foreground hover:bg-primary/70',
    destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
    confirmative: 'bg-confirmative text-confirmative-foreground hover:bg-confirmative/90',
    outline: 'border border-input bg-transparent hover:bg-background hover:text-background-foreground',
    'outline-secondary': 'text-secondary border border-secondary bg-transparent hover:bg-secondary hover:text-secondary-foreground',
    ghost: 'hover:bg-accent hover:text-accent-foreground',
    link: 'text-primary underline-offset-4 hover:underline',
  },
  size: {
    default: 'h-9 px-4 py-1',
    sm: 'h-7 rounded-md px-3',
    lg: 'h-11 rounded-md px-8',
    icon: 'h-10 w-10',
  },
},
    defaultVariants: {
        variant: 'default',
        size: 'default',
    }
};

const resolve = variantAuthority(style)
const props = defineProps({
  type: {
    type: String,
    required: false,
  },
    variant: {
      type: String,
        required: false,
    },
    size: {
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
