<script setup lang="ts">
import Button from '../../Components/interactive/Button.vue';
import DialogBase from '../DialogBase.vue';
import { useHelpDialog } from './useHelpDialog';
import { ChevronRightIcon } from "@heroicons/vue/24/solid";
import {ref} from "vue";
import TextInput from "../../form/TextInput.vue";
import TextArea from "../../form/TextArea.vue";
import Checkbox from "../../form/Checkbox.vue";
import InputLabel from "../../form/InputLabel.vue";
import ActivityIndicator from "../../Components/ActivityIndicator.vue";
import { useSubmissionStatus } from '../useSubmissionStatus';

const {isActive, close: closeHelp, submit} = useHelpDialog();
const { setIdle, setDisabled, setError, setSubmitting, error, status, disabled, message, setSuccess } = useSubmissionStatus();
const emit = defineEmits(['closed']);
defineProps({
  static: {
    type: Boolean,
    defaultValue: true,
  },
});

const form = ref({
  title: '',
  summary: '',
  screenshot: null,
  attachLog: false,
  sendCopy: false,
  canContact: false,
});
const type = ref();
const fileError = ref();
const close = () => {
  type.value = null;
  fileError.value = null;
  form.value = {
      title: '',
      summary: '',
      screenshot: null,
      attachLog: false,
      sendCopy: false,
      canContact: false,
  };
  closeHelp();
  emit('closed');
};
const onFileChange = (e) => {
    const files = e.target.files || e.dataTransfer.files;
    if (!files.length) {
        fileError.value = 'No file selected';
        return;
    }
    const file = files[0];
    if (file.size >  1e6 * 2) {
        fileError.value = 'File size exceeds 2MB';
        return;
    }
    form.value.screenshot = files[0];
}

const submitForm = async (e) => {
    e.preventDefault();
    setSubmitting();
    const res = await submit({
        type: type.value,
        path: window.location.pathname,
        query: Object.fromEntries(new URLSearchParams(window.location.search).entries()),
        ...form.value
    });
    if (res.response?.success) {
        setSuccess('Your request has been submitted successfully.');
        setTimeout(() => {
            close();
            setIdle();
        }, 1500);
    } else {
        setError(
            res.error ?? new Error('An error occurred while submitting your request. Please try again later.')
        );
    }
}
</script>

<template>
  <DialogBase
    title="Help, contact and feedback"
    :show="isActive"
    :static="true"
    @close="close"
  >
    <template #body>
        <div v-if="!type" class="flex items-center my-3">
            <img :src="$page.props.logo" alt="Qdi logo" width="64" height="64" />
            <p class="text-sm">What can I do for you?</p>
        </div>
        <form v-if="!!type" id="feedbackForm" class="w-full" @submit.prevent="submitForm" :inert="disabled ? true : null">
            <TextInput required v-model="form.title" label="Title" maxlength="50" class="w-full mb-3" />
            <TextArea required v-model="form.summary" label="Summary" maxlength="3000" class="w-full mb-3" :rows="10" />
            <InputLabel value="Attach a screenshot" class="mb-1" />
            <input @change="onFileChange" type="file" accept="image/*" class="w-full mb-3" />
            <span class="text-destructive">{{fileError}}</span>
            <Checkbox v-model="form.attachLog" label="Attach the Browser's log output" class="mb-3" />
            <Checkbox v-model="form.sendCopy" label="Send me a copy" class="mb-3" />
            <Checkbox v-model="form.canContact" label="You can contact me back" class="mb-3" />
        </form>
        <ul v-else class="text-sm space-y-3">
            <li class="flex">
                <button @click="type = 'help'" class="flex-grow hover:font-semibold">I need help</button>
                <ChevronRightIcon class="w-4 h-4 text-secondary" />
            </li>
            <li class="flex">
                <button @click="type = 'bug'" class="flex-grow hover:font-semibold">I want to report a problem</button>
                <ChevronRightIcon class="w-4 h-4 text-secondary" />
            </li>
            <li class="flex">
                <button @click="type = 'feedback'" class="flex-grow hover:font-semibold">I want to give feedback</button>
                <ChevronRightIcon class="w-4 h-4 text-secondary" />
            </li>
        </ul>
        <div v-if="error" class="block w-full mt-4 text-destructive">{{error.message}}</div>
    </template>
    <template #footer>
      <div class="flex justify-between items-center w-full">
        <Button variant="outline" @click="close">Cancel</Button>
          <span class="flex">
            <ActivityIndicator v-if="status === 'submitting'" class="mr-1">submitting</ActivityIndicator>
            <Button v-if="type" type="submit" :disabled="disabled" form="feedbackForm">Submit</Button>
          </span>
      </div>
    </template>
  </DialogBase>
</template>

<style scoped></style>
