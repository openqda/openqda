<script setup lang="ts">
import Button from '../Components/interactive/Button.vue';
import DialogBase from './DialogBase.vue';
import { ref, watch } from 'vue';
import FilesImportWizard from '../Components/files/FilesImportWizard.vue';
import ActivityIndicator from '../Components/ActivityIndicator.vue';

const emit = defineEmits(['complete', 'cancelled']);
const error = ref(null);
const complete = ref(false);
const submitting = ref(false);
const open = ref(false);
const props = defineProps({
  schema: { type: Object },
  submit: { type: Function },
  title: { type: String, required: false },
  filesSelected: Function,
  progress: Number,
  accept: { type: String, default: '*/*' },
});
watch(
  () => props.schema,
  (newSchema) => newSchema && start(newSchema)
);

const start = () => {
  open.value = true;
};
const close = () => {
  open.value = false;
  error.value = null;
  submitting.value = false;
  complete.value = false;
};
const cancel = () => {
  close();
  emit('cancelled');
};

const uploading = ref(false);

const importAllFiles = async (files) => {
  uploading.value = true;
  error.value = null;
  try {
    await props.filesSelected(files);
  } catch (e) {
    error.value = e.message;
  } finally {
    uploading.value = false;
  }
  if (!error.value) {
    close();
    emit('complete');
  }
};
</script>

<template>
  <DialogBase :show="open">
    <template #title>Import file(s)</template>
    <template #body>
        <div class="text-sm my-5">
            When uploading files, please make sure
            <ul class="list-disc ml-5 mt-2">
                <li>they are not violating other's privacy or copyright</li>
                <li>they are not violating our <a class="hover:underline text-primary" href="/terms" target="_blank">terms</a></li>
                <li>they do not exceed the maximum allowed size of 10MB</li>
                <li>text-based files are encoded in <a class="hover:underline text-primary" href="/faq#utf8" target="_blank">utf-8</a> format for maximum compatibility</li>
            </ul>
        </div>
      <FilesImportWizard
        class="border-t border-foreground/10 pt-4"
        @files-selected="importAllFiles"
        :accept="props.accept"
        :disabled="uploading"
      >
        <template #info>
          <span v-if="uploading || error" class="text-sm">
            <ActivityIndicator v-if="uploading">
              <span>uploading files...</span>
              <span v-if="props.progress">{{ props.progress }}%</span>
            </ActivityIndicator>
          </span>
          <span class="text-destructive" v-if="error">
            {{ error }}
          </span>
        </template>
      </FilesImportWizard>
    </template>
    <template #close>
      <Button variant="ghost" :disabled="uploading" size="sm" @click="cancel"
        >&times;</Button
      >
    </template>
  </DialogBase>
</template>

<style scoped></style>
