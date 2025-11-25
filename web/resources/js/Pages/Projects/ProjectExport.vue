<script setup lang="ts">
import { ref } from 'vue';
import Button from '../../Components/interactive/Button.vue';
import { useProjects } from '../../domain/project/useProjects';
import ActivityIndicator from '../../Components/ActivityIndicator.vue';
import InputLabel from '../../form/InputLabel.vue';
import Headline3 from '../../Components/layout/Headline3.vue';

const props = defineProps(['project']);
const exportError = ref(null);
const exporting = ref(false);
const { exportProject } = useProjects();
const selectedFormat = ref('openqda');

const runExport = async (e) => {
  e.preventDefault();
  exportError.value = null;
  exporting.value = true;

  const { response, error } = await exportProject({
    projectId: props.project.id,
    type: selectedFormat.value,
  });
  if (!error && response?.data) {
    // Create a link to download the file
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    const timestamp = Date.now();
    const title = props.project.name.replace(/\s+/g, '-');
    link.href = url;
    link.setAttribute('download', `OpenQDA-${title}-${timestamp}.qdpx`); //or any other extension
    document.body.appendChild(link);
    link.click();
    link.remove();
  } else {
    exportError.value = error ?? new Error('Export failed for unknown reasons');
  }
  exporting.value = false;
};
</script>

<template>
  <div class="w-full xl:max-w-5xl">
    <p class="text-sm text-foreground/60 me-3 my-4 py-4">
      Please carefully read the information about the available export formats
      below.
    </p>

    <InputLabel class="my-4 p-4 flex items-center">
      <input
        type="radio"
        name="export-format"
        value="openqda"
        v-model="selectedFormat"
        class="me-2"
      />
      <div class="p-4 text-sm">
        <Headline3>OpenQDA Project Export</Headline3>
        <p class="text-foreground/60">
          You can export your project as an OpenQDA project archive which you
          can later re-import in OpenQDA. It contains all your data, codes,
          memos, and project structure, as well as the full audit log.
        </p>
        <p class="font-semibold text-destructive">
          This format is not compatible with other QDA software.
        </p>
      </div>
    </InputLabel>

    <InputLabel class="my-4 p-4 flex items-center">
      <input
        type="radio"
        name="export-format"
        value="refi"
        v-model="selectedFormat"
        class="me-2"
      />
      <div class="p-4 text-sm">
        <Headline3>REFI-compliant Export</Headline3>
        <p class="text-foreground/60">
          You can export your project as a REFI-compliant .qdpx archive. It
          enables you to share your project with other QDA software that
          supports the REFI standard. Most commercial QDA software support this
          format.
        </p>
        <p class="font-semibold text-destructive">
          Please note, that REFI currently does not support extra data, such as
          the OpenQDA audit log.
        </p>
      </div>
    </InputLabel>

    <div v-if="exportError" class="text-destructive mt-2">
      {{ exportError.message }}
    </div>
    <div class="my-4 py-4">
      <Button :disabled="exporting" @click="runExport">
        <ActivityIndicator v-if="exporting" :label="false" />
        Export Project
      </Button>
    </div>
  </div>
</template>

<style scoped></style>
