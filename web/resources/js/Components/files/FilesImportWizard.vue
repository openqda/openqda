<script setup lang="ts">
import Wizard from '../wizard/Wizard.vue';
import WizardStep from '../wizard/WizardStep.vue';
import Dropzone from './Dropzone.vue';
import FileUploadButton from './FileUploadButton.vue';
import { ref } from 'vue';
import Button from '../interactive/Button.vue';

const emit = defineEmits(['filesSelected']);
const localFiles = ref([]);
const fileKey = (file) => `${file.name}-${file.type}-${file.size}`;
const addFiles = (files) => {
  const unique = new Set();
  localFiles.value.forEach((file) => unique.add(fileKey(file)));
  files.forEach((file) => {
    const key = fileKey(file);
    if (!unique.has(key)) {
      localFiles.value.push(file);
    }
    unique.add(key);
  });
};
const uploadFiles = async () => {
  emit('filesSelected', localFiles.value);
};
</script>

<template>
  <Wizard start="location">
    <WizardStep
      name="location"
      title="Select Location"
      :options="[
        { next: 'localUpload', label: 'From my computer' },
        { next: 'remoteImport', label: 'From a remote location' },
      ]"
    >
      <p class="text-sm text-foreground/80">
        Please select from where you want to import the files from.
      </p>
    </WizardStep>

    <WizardStep name="remoteImport" title="Select remote">
      <p class="text-sm text-foreground/80">
        Stay tuned! This feature is coming soon!
      </p>
    </WizardStep>

    <WizardStep name="localUpload" title="Add your files">
      <Dropzone
        accept=".txt,.rtf,audio/*"
        @files-dropped="addFiles"
        class="w-full min-h-20 border-dashed border-4 rounded-lg border-foreground/60 flex items-center justify-center text-foreground/60 text-sm"
      >
        <div class="w-full h-auto p-10 text-center">
          <FileUploadButton
            label="Select files"
            class="my-1"
            :multiple="true"
            accept=".txt,.rtf,audio/*"
            @file-added="addFiles"
          >
            <span class="cursor-pointer hover:underline"
              >Drop the files you want to upload here or click to select
              files.</span
            >
          </FileUploadButton>
        </div>
      </Dropzone>
      <table v-if="localFiles.length" class="table my-5 text-sm">
        <tr v-for="(file, index) in localFiles" :key="file.name">
          <td class="w-full">{{ file.name }}</td>
          <td>{{ Math.round(file.size / 1000) }}kb</td>
          <td>
            <Button
              variant="ghost"
              size="sm"
              @click.prevent="localFiles.splice(index, 1)"
              >&times;</Button
            >
          </td>
        </tr>
      </table>
      <Button
        v-if="localFiles.length"
        size="lg"
        class="w-full mt-5"
        @click.prevent="uploadFiles"
        >Start upload</Button
      >
    </WizardStep>
  </Wizard>
</template>

<style scoped></style>
