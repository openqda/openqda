<script setup>
import InputLabel from './InputLabel.vue';
import InputError from '../../../vendor/laravel/jetstream/stubs/inertia/resources/js/Components/InputError.vue';
import { cn } from '../utils/css/cn.js';

const props = defineProps({
  label: String,
  title: String,
  class: String,
  labelClass: String,
  defaultValue: Boolean,
  validation: Object,
});
</script>

<template>
  <div class="input-group group contents" :class="cn(props.class)">
    <InputLabel class="flex items-center" :title="props.title">
      <input
        type="checkbox"
        v-bind="$attrs"
        :checked="props.defaultValue"
        :class="
          cn(
            'outline outline-0 px-0.5',
            'focus:ring-foreground/80',
            'checked:bg-primary'
          )
        "
      />
      <span
        v-if="props.label"
        :class="cn('ms-2 text-nowrap', props.labelClass)"
        >{{ props.label }}</span>
        <slot name="label"></slot>
    </InputLabel>
    <InputError
      v-if="props.validation?.isValid === false"
      :message="props.validation?.error"
    />
  </div>
</template>
