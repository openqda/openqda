<script setup lang="ts">
import Button from '../Components/interactive/Button.vue';
import DialogBase from './DialogBase.vue';
import ActionMessage from '../Components/ActionMessage.vue';
import AutoForm from '../form/AutoForm.vue';
import { ref, watch } from 'vue';
import { asyncTimeout } from '../utils/asyncTimeout';

/*----------------------------------------------------------------------------/
 | FormDialog
 |----------------------------------------------------------------------------/
 | A generic dialog that shows a form based on a given schema.
 | It allows to connect a submit function that is called when the form is submitted.
 | You can trigger it either by using the trigger slot
 | or by using the useFormDialog composable.
 | You should use the trigger slot when there is only a single trigger for the dialog.
 | In turn, if you have multiple triggers, you should use the composable.
 | A typical use case for that is to have a list of items that can all open the same dialog.
 |
 | It emits 'created' event when the form is successfully submitted
 | and 'cancelled' event when the dialog is closed without submission.
 *----------------------------------------------------------------------------*/

const props = defineProps({
  id: { type: String },
  static: { type: Boolean, required: false },
  schema: { type: Object },
  submit: { type: Function },
  title: { type: String, required: false },
  buttonTitle: String,
  show: Boolean,
});

const emit = defineEmits(['created', 'cancelled']);
const error = ref(null);
const complete = ref(false);
const submitting = ref(false);
const open = ref(false);

watch(
  () => props.show,
  (newVal) => {
    if (newVal !== open.value) setTimeout(() => (open.value = newVal), 100);
  },
  { immediate: true }
);

const start = (callback) => {
  callback();
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
      error.value = 'Submission failed due to unknown reasons';
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

const keyDownHandler = (e) => {
  if (e.key === 'Escape') {
    cancel();
  }
};
</script>

<template>
  <div>
    <slot name="trigger" @click="start" :trigger="start"></slot>
    <DialogBase
      :title="props.title ?? 'Rename'"
      :show="open"
      :static="props.static"
      :show-close-button="true"
      @close="cancel"
      @keydown="keyDownHandler"
    >
      <template #body>
        <AutoForm
          v-if="props.schema"
          id="create-custom-form"
          :autofocus="true"
          :schema="props.schema"
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
  </div>
</template>

<style scoped></style>
