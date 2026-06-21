<script setup lang="ts">
import Button from '../Components/interactive/Button.vue';
import { randomString } from '../utils/random/randomString';
import DialogBase from './DialogBase.vue';
import { ref, watch } from 'vue';
import TextInput from '../form/TextInput.vue';

const emit = defineEmits(['confirmed', 'cancelled']);
const open = ref(false);

const props = defineProps({
  title: String,
  text: String,
  showConfirm: Boolean,
  showCancel: {
    type: Boolean,
    default: true,
  },
  cancelButtonLabel: String,
  confirmButtonLabel: String,
  destructive: {
    type: Boolean,
    default: true,
  },
  static: {
    type: Boolean,
    default: true,
  },
  challenge: {
    type: Boolean,
    default: false,
  },
  fatal: {
    type: Boolean,
    default: false,
  },
});

watch(
  () => props.text,
  () => start()
);

const currentChallenge = ref('');
const enteredChallenge = ref('');
const confirmedFatal = ref(false);
const start = () => {
  open.value = true;
  confirmedFatal.value = false;
  enteredChallenge.value = '';

  if (typeof props.challenge === 'string') {
    currentChallenge.value = props.challenge;
  } else if (props.challenge === true) {
    currentChallenge.value = randomString(4, 'math');
  } else {
    currentChallenge.value = '';
  }
};

const confirm = () => {
  if (currentChallenge.value != enteredChallenge.value) {
    return;
  }
  open.value = false;
  confirmedFatal.value = false;
  emit('confirmed');
};

const cancel = () => {
  open.value = false;
  confirmedFatal.value = false;
  emit('cancelled');
};
</script>

<template>
  <DialogBase
    :title="props.title ?? 'Confirm decision'"
    :show="open"
    :destructive="destructive"
    @close="cancel"
  >
    <template #body>
      <p>{{ props.text }}</p>
      <slot name="info"></slot>
      <div
        v-if="currentChallenge.length && (!fatal || confirmedFatal)"
        class="w-100 text-center my-4"
      >
        <div
          class="w-full text-center font-semibold font-mono tracking-widest text-foreground"
        >
          {{ currentChallenge }}
        </div>
        <TextInput
          v-model="enteredChallenge"
          placeholder="Enter to confirm"
          type="text"
          class="w-full text-center font-semibold font-mono tracking-widest text-foreground"
          @keydown.enter="confirm"
          autofocus
          :maxlength="currentChallenge.length"
        />
      </div>
    </template>
    <template #footer>
      <div v-if="fatal && !confirmedFatal" class="py-3">
        <span class="!text-destructive bg-surface font-semibold px-1"
          >This action can be fatal!</span
        >
        <p class="py-3">
          Heads up! Did you understood the consequences, mentioned above?
        </p>
        <Button
          variant="destructive"
          class="block w-full text-destructive-foreground !text-lg font-semibold"
          size="lg"
          @click="confirmedFatal = true"
        >
          I understand and want to proceed
        </Button>
      </div>
      <div v-else class="flex justify-between items-center w-full">
        <Button v-if="props.showCancel" variant="outline" @click="cancel"
          >{{ props.cancelButtonLabel ?? 'Cancel' }}
        </Button>
        <Button
          v-if="props.showConfirm"
          :disabled="
            currentChallenge?.length > 0 && enteredChallenge != currentChallenge
          "
          @click="confirm"
          :variant="destructive ? 'destructive' : 'default'"
          >{{ props.confirmButtonLabel ?? 'Confirm' }}
        </Button>
      </div>
    </template>
  </DialogBase>
</template>

<style scoped></style>
