<script setup lang="ts">
import Button from '../Components/interactive/Button.vue';
import DialogBase from './DialogBase.vue';
import ActionMessage from '../Components/ActionMessage.vue';
import AutoForm from '../form/AutoForm.vue';
import { ref, watch } from 'vue';
import { asyncTimeout } from '../utils/asyncTimeout';

const props = defineProps({
  schema: { type: Object },
  submit: { type: Function },
  title: { type: String, required: false },
  buttonTitle: String,
});

const emit = defineEmits(['created', 'cancelled']);
const error = ref(null);
const complete = ref(false);
const submitting = ref(false);
const open = ref(false);
const currentSchema = ref(null);
watch(
  () => props.schema,
  (newSchema) => newSchema && start(newSchema)
);

const start = (newSchema) => {
  currentSchema.value = newSchema;
  open.value = true;
};
const submit = async (document) => {
  error.value = false; // Clear any previous error
  complete.value = false;
  submitting.value = true;
  await asyncTimeout(300);

  let created = null;
  try {
    created = await props.submit(document);
  } catch (e) {
    console.error('Error during form submission:', e);
    error.value =
      e.response?.data?.message ??
      e.message ??
      'An unknown error occurred while submitting.';
    created = null;
  } finally {
    submitting.value = false;
  }

  if (!created) {
    if (!error.value) {
      error.value = 'Create failed due to unknown reasons';
    }
    return;
  } else {
    complete.value = true;
  }

  if (complete.value) {
    setTimeout(() => {
      complete.value = false;
      open.value = false;
      submitting.value = false;
      emit('created', created);
    }, 300);
  }
};
const cancel = () => {
  open.value = false;
  error.value = null;
  submitting.value = false;
  complete.value = false;
  emit('cancelled');
};
</script>

<template>
  <DialogBase :title="props.title ?? 'Rename'" :show="open">
    <template #body>
      <AutoForm
        v-if="currentSchema"
        id="create-custom-form"
        :autofocus="true"
        :schema="currentSchema"
        @submit="submit"
        class="w-full"
        :show-cancel="false"
        :show-submit="false"
      />
      <slot name="info"></slot>
    </template>
    <template #footer>
      <div class="flex justify-between items-center w-full">
        <Button variant="outline" @click="cancel">Cancel</Button>
        <span class="grow text-right mx-1">
          <ActionMessage
            v-if="!complete && !error"
            :on="submitting"
            class="text-secondary"
            >Saving</ActionMessage
          >
          <ActionMessage :on="complete" class="text-secondary"
            >Saved</ActionMessage
          >
          <ActionMessage :on="!!error" class="text-destructive">{{
            error
          }}</ActionMessage>
        </span>
        <Button
          type="submit"
          form="create-custom-form"
          :disabled="submitting"
          >{{ props.buttonTitle ?? 'Create' }}</Button
        >
      </div>
    </template>
  </DialogBase>
</template>

<style scoped></style>
