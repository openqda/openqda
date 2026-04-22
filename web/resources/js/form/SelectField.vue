<script setup lang="ts">
import { ref } from 'vue';
import InputLabel from './InputLabel.vue';
import InputError from '../../../vendor/laravel/jetstream/stubs/inertia/resources/js/Components/InputError.vue';
import { cn } from '../utils/css/cn';
import { variantAuthority } from '../utils/css/variantAuthority';

const props = defineProps({
  value: String,
  name: String,
  class: String,
  groupClass: String,
  options: Array,
  label: String,
  id: String,
  size: {
    type: String,
    default: 'default',
  },
  defaultOption: Boolean,
  emptyOptionTitle: {
    type: String,
    default: '(Select one)',
  },
});
const current = ref(props.value);
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

<template>
  <div :class="cn('input-group group contents', props.groupClass)">
    <InputLabel :for="props.name" :value="props.label" />
    <!-- Use an "onChange" listener to redirect the user to the selected tab URL. -->
    <select
      :id="props.name"
      v-model="current"
      :name="props.name"
      :class="
        cn(
          'block w-full rounded-md focus:border-secondary focus:ring-secondary bg-surface text-foreground',
          resolveText({ size: props.size }),
          props.class
        )
      "
      @change="(e) => (current = e.target.value)"
    >
      <option v-if="props.options || props.defaultOption" disabled value="">
        {{ emptyOptionTitle }}
      </option>
      <option
        v-if="props.options"
        v-for="opt in props.options"
        :key="opt.value"
        :value="opt.value"
      >
        {{ opt.label }}
      </option>
      <slot name="options"></slot>
    </select>
    <InputError
      v-if="props.validation?.isValid === false"
      :message="props.validation?.error"
    />
  </div>
</template>

<style scoped></style>
