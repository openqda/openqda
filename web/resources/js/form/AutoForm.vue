<script setup>
/*----------------------------------------------------
 | AutoForm is a lightweight HoC that constructs forms
 | by given schema.
 *--------------------------------------------------*/
import { onMounted, ref } from 'vue';
import { cn } from '../utils/css/cn.js';
import { transformSchema } from './transformSchema.js';
import { initForms } from './initForms.js';
import InputError from '../../../vendor/laravel/jetstream/stubs/inertia/resources/js/Components/InputError.vue';
import Button from '../Components/interactive/Button.vue';

// make sure we have registered all elements
initForms();

const props = defineProps({
  id: String,
  schema: Object,
  showSubmit: {
    type: Boolean,
    default: true,
  },
  autofocus: {
    type: Boolean,
    default: true,
  },
  showCancel: {
    type: Boolean,
    default: true,
  },
  class: {
    type: String,
    required: false,
  },
  size: {
    type: String,
    default: 'default',
  },
});
const emit = defineEmits(['cancel', 'submit']);
const fields = ref(null);
const validationErrors = ref({});
const submitFailed = ref(false);

onMounted(() => {
  fields.value = Object.entries(props.schema).map(([key, value]) => {
    return transformSchema(value, key);
  });
});

const validateForm = (e) => {
  e.preventDefault();
  e.stopPropagation();
  const formData = new FormData(e.target);
  const data = [...formData.entries()];
  const validation = {};
  let isValid = true;
  data.forEach(([key, value]) => {
    const schema = fields.value.find((s) => s.data.name === key);
    const result = schema.data.validate(key, value);
    // result: key, value, valid, error
    validation[key] = result;
    // if one field fails, fail form
    if (!result.valid) {
      isValid = false;
    }
  });

  if (!isValid) {
    validationErrors.value = validation;
    submitFailed.value = true;
    e.stopImmediatePropagation();
    return false;
  }

  const toSubmit = Object.fromEntries(data);
  emit('submit', toSubmit);
};
</script>

<template>
  <form
    :id="props.id"
    :class="cn('bg-transparent', $props.class)"
    @submit="validateForm"
  >
    <component
      v-for="({ component, data }, index) in fields"
      :key="index"
      :is="component"
      v-bind="data"
      :size="props.size"
      :value="data.defaultValue"
      :required="data.required"
      :validation="validationErrors[data.name]"
    ></component>

    <div class="block w-full text-center" v-if="submitFailed">
      <InputError message="Form could not be submitted" />
    </div>

    <div
      v-if="showSubmit !== false || showCancel !== false"
      class="flex items-center justify-between mt-2"
    >
      <Button variant="outline" :size="props.size" @click="$emit('cancel')"
        >Cancel</Button
      >
      <Button type="submit" :size="props.size" class="float-right"
        >Submit</Button
      >
    </div>
  </form>
</template>
