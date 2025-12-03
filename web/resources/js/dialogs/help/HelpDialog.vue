<script setup lang="ts">
import Button from '../../Components/interactive/Button.vue';
import DialogBase from '../DialogBase.vue';
import { useHelpDialog } from './useHelpDialog';
import ActivityIndicator from '../../Components/ActivityIndicator.vue';
import { useSubmissionStatus } from '../useSubmissionStatus';
import { useProjects } from '../../domain/project/useProjects';
import AutoForm from '../../form/AutoForm.vue';
import Link from '../../Components/Link.vue';

const { isActive, close: closeHelp, submit } = useHelpDialog();
const { projectId } = useProjects();
const {
  setIdle,
  setError,
  setSubmitting,
  error,
  status,
  disabled,
  message,
  setSuccess,
} = useSubmissionStatus();
const emit = defineEmits(['closed']);
defineProps({
  static: {
    type: Boolean,
    defaultValue: true,
  },
});

const feedbackSchema = {
  type: {
    type: String,
    label: 'Category',
    formType: 'select',
    required: true,
    defaultValue: 'help',
    options: [
      { label: 'Help needed', value: 'help' },
      { label: 'Report a Problem', value: 'bug' },
      { label: 'Feedback', value: 'feedback' },
    ],
  },
  summary: {
    type: String,
    label: 'Summary',
    formType: 'textarea',
    required: true,
    placeholder: 'Please provide a brief summary of your request',
  },
  // TODO: uncomment when we have logging in place
  // attachLog: {
  //     type: Boolean,
  //     label: 'Attach Browser Log',
  //     required: false
  // }
};

const close = () => {
  setIdle();
  closeHelp();
  emit('closed');
};

const submitForm = async (formData) => {
  setSubmitting();
  const { summary, type, attachLog } = formData;
  const res = await submit({
    projectId: projectId ? String(projectId) : null,
    path: window.location.pathname,
    query: window.location.search,
    type,
    summary,
    attachLog: attachLog === 'on' || attachLog === true,
  });

  if (res.response?.data?.sent) {
    setSuccess('Your request has been submitted successfully.');
    setTimeout(() => {
      close();
      setIdle();
    }, 1500);
  } else {
    setError(
      new Error(
        res.response?.data?.message ??
          'An error occurred while submitting your request. Please try again later.'
      )
    );
  }
};
</script>

<template>
  <DialogBase
    title="Help, contact and feedback"
    :show="isActive"
    :static="true"
    @close="close"
  >
    <template #body>
      <div class="flex items-center my-3">
        <img :src="$page.props.logo" alt="Qdi logo" width="64" height="64" />
        <p class="text-sm">
          We explain how to use the app in the
          <Link href="https://openqda.github.io/user-docs/" :external="true"
            >OpenQDA documentation</Link
          >. If you have any further questions or comments, please use the
          following form.
        </p>
      </div>
      <AutoForm
        id="feedbackForm"
        :autofocus="true"
        :schema="feedbackSchema"
        @submit="submitForm"
        class="w-full"
        :show-cancel="false"
        :show-submit="false"
      />
      <div v-if="error" class="block w-full mt-4 text-destructive">
        {{ error.message }}
      </div>
      <div v-if="message" class="block w-full mt-4">{{ message }}</div>
    </template>
    <template #footer>
      <div class="flex justify-between items-center w-full">
        <Button variant="outline" @click="close">Cancel</Button>
        <span class="flex">
          <ActivityIndicator v-if="status === 'submitting'" class="mr-1"
            >submitting</ActivityIndicator
          >
          <Button type="submit" :disabled="disabled" form="feedbackForm"
            >Submit</Button
          >
        </span>
      </div>
    </template>
  </DialogBase>
</template>

<style scoped></style>
