<script setup>
import InputLabel from './InputLabel.vue';
import { useAttrs } from 'vue';
import InputError from '../../../vendor/laravel/jetstream/stubs/inertia/resources/js/Components/InputError.vue';
import { cn } from '../utils/css/cn.js';

const atts = useAttrs();
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
      <input type="checkbox" v-bind="$attrs" :checked="props.defaultValue" />
      <span
        v-if="props.label"
        :class="cn('ms-1 text-nowrap', props.labelClass)"
        >{{ props.label }}</span
      >
    </InputLabel>
    <InputError
      v-if="props.validation?.isValid === false"
      :message="props.validation?.error"
    />
  </div>
</template>
