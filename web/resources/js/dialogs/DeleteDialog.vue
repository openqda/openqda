<script setup lang="ts">
import { ref, watch } from 'vue';
import Button from '../Components/interactive/Button.vue';
import DialogBase from './DialogBase.vue';
import TextInput from '../form/TextInput.vue';
import InputError from '../form/InputError.vue';
import ActionMessage from '../Components/ActionMessage.vue';
import { randomString } from '../utils/randomString';
import Headline3 from "../Components/layout/Headline3.vue";
import { asyncTimeout } from '../utils/asyncTimeout';

const emit = defineEmits(['deleted', 'cancelled']);
const props = defineProps({
  target: {
    type: Object,
  },
  submit: {
    type: Function,
  },
  challenge: { type: String, required: false },
  dependants: { type: Array, required: false },
    message: String
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
            emit('created', deleted);
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
  reset();
};
</script>

<template>
  <DialogBase :show="open">
    <template #title>
        <p v-if="props.message" class="text-center py-6 px-2 bg-destructive/20 rounded-md mb-4">
            <Headline3 class="block bold text-destructive text-xl">Attention!</Headline3>
            <span class="font-normal">{{props.message}}</span>
        </p>
      <div class="w-full text-center">
        Sure you want to delete
        <span class="text-secondary/80">{{ target?.name }}</span
        >?
      </div>
    </template>
    <template #body>
      <div v-if="!!currentChallenge">
        <p class="text-center text-foreground/60">
          If so, please enter the following code to confirm deletion
        </p>
        <p
          class="w-full text-center font-semibold text-xl font-mono tracking-widest text-foreground py-2"
        >
          {{ currentChallenge }}
        </p>
        <TextInput
          v-model="challengeEntered"
          :placeholder="`Enter ${currentChallenge} to confirm`"
          type="text"
          class="w-full text-center font-semibold text-xl font-mono tracking-widest text-foreground"
          @keydown.enter="submit()"
          autofocus
        />
        <InputError v-if="error">{{ error }}</InputError>
      </div>
    </template>
    <template #footer>
      <div class="flex justify-between items-center w-full">
        <Button variant="outline" @click="cancel">Cancel</Button>
        <span class="flex-grow text-right mx-1">
          <ActionMessage
            v-if="!complete && !error"
            :on="submitting"
            class="text-destructive"
            >Deleting</ActionMessage
          >
          <ActionMessage :on="complete" class="text-destructive"
            >Deleted</ActionMessage
          >
          <ActionMessage :on="error" class="text-destructive">{{
            error
          }}</ActionMessage>
        </span>
        <Button
          variant="destructive"
          @click="submit"
          :disabled="
            submitting || (currentChallenge && challengeEntered !== currentChallenge)
          "
          >Delete
        </Button>
      </div>
    </template>
  </DialogBase>
</template>

<style scoped></style>
