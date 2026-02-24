<script setup lang="ts">
import { ref, watch } from 'vue';
import Button from '../Components/interactive/Button.vue';
import DialogBase from './DialogBase.vue';
import TextInput from '../form/TextInput.vue';
import InputError from '../form/InputError.vue';
import ActionMessage from '../Components/ActionMessage.vue';
import Headline3 from '../Components/layout/Headline3.vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline/index.js';
import { randomString } from '../utils/random/randomString';
import { asyncTimeout } from '../utils/asyncTimeout';

const emit = defineEmits(['deleted', 'cancelled', 'close']);
const props = defineProps({
  target: {
    type: Object,
  },
  submit: {
    type: Function,
  },
  challenge: { type: String, required: false },
  dependants: { type: Array, required: false },
  message: String,
});

const currentChallenge = ref(null);
const challengeEntered = ref(null);
const error = ref(null);
const complete = ref(false);
const submitting = ref(false);
const open = ref(false);
const id = ref(null);
const name = ref(null);

watch(
  () => props.target,
  (newFile) => newFile && start(newFile)
);

const start = (file) => {
  id.value = file.id;
  name.value = file.name;
  open.value = true;
  error.value = null;
  submitting.value = false;
  complete.value = false;
  switch (props.challenge) {
    case 'random':
      challengeEntered.value = '';
      currentChallenge.value = randomString(4, 'random');
      break;
    case 'name':
      challengeEntered.value = '';
      currentChallenge.value = props.target.name;
      break;
    default:
      currentChallenge.value = null;
  }
};

const submit = async () => {
  error.value = false; // Clear any previous error
  complete.value = false;
  submitting.value = true;
  await asyncTimeout(300);

  let deleted = null;
  try {
    deleted = await props.submit(props.target);
  } catch (e) {
    console.error('Error during form submission:', e);
    error.value =
      e.response?.data?.message ??
      e.message ??
      'An unknown error occurred while submitting.';
    deleted = null;
  } finally {
    submitting.value = false;
  }

  if (!deleted) {
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
      emit('deleted', deleted);
      emit('close');
    }, 300);
  }
};

const reset = () => {
  open.value = false;
  id.value = null;
  name.value = null;
  error.value = null;
  submitting.value = false;
  complete.value = false;
};

const cancel = () => {
  emit('cancelled');
  emit('close');
  reset();
};
</script>

<template>
  <DialogBase :show="open">
    <template #title>
      <div v-if="props.message" class="py-6 font-normal">
        <Headline3
          class="block font-semibold mb-4 border rounded-md border-destructive p-2"
        >
          <ExclamationTriangleIcon
            class="text-destructive w-6 h-6 inline-block mr-1"
          />
          <span class="text-destructive">Attention!</span>
        </Headline3>
        <p class="text-sm">
          Are you sure you want to delete
          <span class="font-semibold text-destructive"
            >"{{ target?.name }}"</span
          >?
        </p>
        <ul class="list-disc list-outside mt-2 text-sm ps-4 leading-6">
          <li>This action cannot be undone.</li>
          <li>It will be added to the audit trail</li>
          <li v-if="props.message">{{ props.message }}</li>
        </ul>
      </div>
      <div class="w-full"></div>
    </template>
    <template #body>
      <div v-if="!!currentChallenge">
        <p class="text-sm px-2 text-center">
          If you are sure to continue, please enter the following to confirm
          deletion:
        </p>
        <p
          class="w-full text-center font-semibold font-mono tracking-widest text-foreground py-2"
        >
          {{ currentChallenge }}
        </p>
        <TextInput
          v-model="challengeEntered"
          placeholder="Enter to confirm"
          type="text"
          class="w-full text-center font-semibold font-mono tracking-widest text-foreground"
          @keydown.enter="submit()"
          autofocus
        />
        <InputError v-if="error">{{ error }}</InputError>
      </div>
    </template>
    <template #footer>
      <div class="flex justify-between items-center w-full">
        <Button variant="outline" @click="cancel">Cancel</Button>
        <span class="grow text-right mx-1">
          <ActionMessage
            v-if="!complete && !error"
            :on="!!submitting"
            class="text-destructive"
            >Deleting</ActionMessage
          >
          <ActionMessage :on="!!complete" class="text-destructive"
            >Deleted</ActionMessage
          >
          <ActionMessage :on="!!error" class="text-destructive">{{
            error
          }}</ActionMessage>
        </span>
        <Button
          variant="destructive"
          @click="submit"
          :disabled="
            submitting ||
            (currentChallenge && challengeEntered !== currentChallenge)
          "
          >Delete
        </Button>
      </div>
    </template>
  </DialogBase>
</template>

<style scoped></style>
