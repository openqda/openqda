<script setup>
/*----------------------------------------------------
 | AutoForm is a lightweight HoC that constructs forms
 | by given schema.
 *--------------------------------------------------*/
import { onMounted, ref } from 'vue';
import { cn } from '../../utils/css/cn.js'
import { transformSchema } from './transformSchema.js';
import { initForms } from './initForms.js';
import InputError from '../../../../vendor/laravel/jetstream/stubs/inertia/resources/js/Components/InputError.vue';
import Button from '../../Components/interactive/Button.vue'

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
    }
});
defineEmits(['cancel']);
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
  const fields = [...formData.entries()];
  const validation = {};
  let isValid = true;
  fields.forEach(([key, value]) => {
    validation[key] = {
      isValid: false,
      error: 'fooo bar baz',
    };
    isValid = false;
  });

  if (!isValid) {
    validationErrors.value = validation;
    submitFailed.value = true;
    e.stopImmediatePropagation();
    return false;
  }
};
</script>

<template>
  <form :id="props.id" :class="cn('bg-transparent', $props.class)" @submit="validateForm">
    <component
      v-for="{ component, data } in fields"
      :is="component"
      :type="data.type"
      :label="data.label"
      :name="data.name"
      :validation="validationErrors[data.name]"
      class="mb-4"
    ></component>
    <div v-if="showSubmit !== false || showCancel !== false" class="w-100">
      <Button variant="outline" @click="$emit('cancel')">Cancel</Button>
      <Button type="submit" class="float-right">Submit</Button>
    </div>
    <div class="block w-full text-center" v-if="submitFailed">
      <InputError message="Form could not be submitted" />
    </div>
  </form>
</template>
