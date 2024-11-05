<script setup>
import InputLabel from './InputLabel.vue';
import { useAttrs } from 'vue';
import InputField from './InputField.vue';
import InputError from '../../../vendor/laravel/jetstream/stubs/inertia/resources/js/Components/InputError.vue';

const atts = useAttrs();
const props = defineProps({
  label: String,
  validation: Object,
});
console.debug(atts)
</script>

<template>
  <div class="input-group group contents">
    <InputLabel :value="props.label" />
    <InputField v-bind="$attrs" />
    <InputError
      v-if="props.validation?.isValid === false"
      :message="props.validation?.error"
    />
  </div>
</template>
