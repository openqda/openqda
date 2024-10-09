<script setup lang="ts">
import { ref, watch } from 'vue';
import Button from '../Components/interactive/Button.vue';
import DialogBase from './DialogBase.vue';
import TextInput from '../form/TextInput.vue';
import InputError from '../form/InputError.vue';
import ActionMessage from '../Components/ActionMessage.vue';
import { randomString } from '../utils/randomString';

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
});

const challenge = ref(null);
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
      challenge.value = randomString(4, 'random');
      break;
    case 'name':
      challengeEntered.value = '';
      challenge.value = props.target.name;
      break;
    default:
      challenge.value = null;
  }
};

const submit = async () => {
    submitting.value = true
    const onError = e => {
        console.error(e)
    }
    const options = { id: id.value, name: name.value }
    try {
        const response = await props.submit(options)
        if (response?.error) {
            return onError(response.error)
        }
        emit('deleted', options)
        reset()
    } catch (e) {
        onError(e)
    } finally {
        submitting.value = false
    }
};

const reset = () => {
    open.value = false;
    id.value = null;
    name.value = null;
    error.value = null;
    submitting.value = false;
    complete.value = false;
}

const cancel = () => {
  emit('cancelled');
  reset()
};
</script>

<template>
  <DialogBase :show="open">
    <template #title>
      <div class="w-full text-center">
        Sure you want to delete
        <span class="text-secondary/80">{{ target?.name }}</span>?
      </div>
    </template>
    <template #body>
      <div v-if="!!challenge">
        <p class="text-center text-foreground/60">
          If so, please enter the following code to confirm deletion
        </p>
        <p
          class="w-full text-center font-semibold text-xl font-mono tracking-widest text-foreground py-2"
        >
          {{ challenge }}
        </p>
        <TextInput
          v-model="challengeEntered"
          :placeholder="`Enter ${challenge} to confirm`"
          type="text"
          class="w-full text-center font-semibold text-xl font-mono tracking-widest text-foreground"
          @keydown.enter="submit"
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
        <Button variant="destructive" @click="submit" :disabled="submitting || (challenge && challengeEntered !== challenge)"
          >Delete
        </Button>
      </div>
    </template>
  </DialogBase>
</template>

<style scoped></style>
