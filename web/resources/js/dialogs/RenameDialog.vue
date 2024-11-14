<script setup lang="ts">
import { ref, watch } from 'vue';
import DialogBase from './DialogBase.vue';
import TextInput from '../form/TextInput.vue';
import Button from '../Components/interactive/Button.vue';
import InputError from '../form/InputError.vue';
import ActionMessage from '../Components/ActionMessage.vue';
import { asyncTimeout } from '../utils/asyncTimeout';

const props = defineProps({
    id: String,
  target: {
    type: Object,
  },
  submit: {
    type: Function,
  },
  emptyAllowed: {
    type: Boolean,
    required: false,
    default: false,
  },
  title: { type: String, required: false },
    schema: Object
});

const emit = defineEmits(['renamed', 'cancelled']);
const newName = ref(null);
const error = ref(null);
const complete = ref(false);
const submitting = ref(false);
const open = ref(false);
const id = ref(null);

watch(
  () => props.target,
  (newFile) => newFile && start(newFile)
);


const start = (file) => {
  id.value = file.id;
  newName.value = file.name;
  open.value = true;
  error.value = null;
  submitting.value = false;
  complete.value = false;
};

const submit = async () => {
  error.value = false; // Clear any previous error
  complete.value = false;

  const shouldPassEmpty = props.emptyAllowed || newName.value?.length > 0;
  if (!shouldPassEmpty) {
    error.value = 'An empty value is not allowed';
    return;
  }

  if (!shouldPassEmpty || newName.value === props.target.name) {
    error.value = 'A different value is required';
    return;
  }

  submitting.value = true;
  await asyncTimeout(300);

  const onError = (e) => {
    console.error('Error renaming document:', e);
    error.value =
      e.response?.data?.message ??
      e.message ??
      'An error occurred while saving.';
  };

  try {
    const data = { id: id.value, name: newName.value };
    const { error } = await props.submit(data);
    if (error) {
      onError(error);
    } else {
      complete.value = true;
    }
  } catch (e) {
    onError(e);
  } finally {
    submitting.value = false;
  }

  if (complete.value) {
    setTimeout(() => {
      complete.value = false;
      open.value = false;
      submitting.value = false;
      emit('renamed', { id: id.value, name: newName.value });
    }, 300);
  }
};

const cancel = () => {
  open.value = false;
  id.value = null;
  newName.value = null;
  error.value = null;
  submitting.value = false;
  complete.value = false;
  emit('cancelled');
};
</script>

<template>
  <DialogBase :title="props.title ?? 'Rename'" :show="open">
    <template #body>
      <TextInput
        v-model="newName"
        type="text"
        class="w-full"
        :value="newName"
        @keydown.enter="submit"
      />
      <InputError v-if="error">{{ error }}</InputError>
    </template>
    <template #footer>
      <div class="flex justify-between items-center w-full">
        <Button variant="outline" @click="cancel">Cancel</Button>
        <span class="flex-grow text-right mx-1">
          <ActionMessage
            v-if="!complete && !error"
            :on="submitting"
            class="text-secondary"
            >Saving</ActionMessage
          >
          <ActionMessage :on="complete" class="text-secondary"
            >Saved</ActionMessage
          >
          <ActionMessage :on="error" class="text-destructive">{{
            error
          }}</ActionMessage>
        </span>
        <Button @click="submit" :disabled="submitting"> Save </Button>
      </div>
    </template>
  </DialogBase>
</template>

<style scoped></style>
