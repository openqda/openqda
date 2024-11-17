<template>
  <div class="flex items-center justify-start">
    <Button
      variant="outline-secondary"
      class="rounded-xl"
      @click="createNewFile"
    >
      <PlusIcon class="h-4 w-4 mr-2"></PlusIcon>
      <span>Create</span>
    </Button>
    <Button
      variant="outline-secondary"
      class="rounded-xl ml-3"
      @click="importSchema = { foo: {} }"
    >
      <PlusIcon class="h-4 w-4 mr-2"></PlusIcon>
      <span>Import</span>
    </Button>
  </div>
  <CreateDialog
    :schema="createSchema"
    title="Create new file"
    :submit="onCreateSubmit"
    @created="onCreated"
    @cancelled="createSchema = null"
  />
  <WizardDialog
    :schema="importSchema"
    title="Import file(s)"
    :files-selected="importFiles"
  />
  <FilesList
    v-if="documents?.length"
    class="mt-5"
    :fixed="true"
    :focus-on-hover="true"
    :documents="documents"
    :actions="[
      {
        id: 'retry-atrain',
        title: 'Retry transcription',
        icon: ArrowPathRoundedSquareIcon,
        onClick({ document }) {
          retryTranscription(document);
        },
        visible(document) {
          if (document.type !== 'audio') return false;
          if (document.converted) return false;
          return document.failed || !document.isConverting;
        },
      },
      {
        id: 'retry-conversion',
        title: 'Retry elaboration',
        icon: CloudArrowUpIcon,
        onClick({ document }) {
          retryConvert(document);
        },
        visible(document) {
          if (document.type !== 'text') return false;
          if (document.converted) return false;
          return document.failed || !document.isConverting;
        },
      },
      {
        id: 'download-source',
        title: 'Download Source File',
        icon: DocumentArrowDownIcon,
        onClick({ document }) {
          downloadSource(document);
        },
        visible(document) {
          return document.converted;
        },
      },
      {
        id: 'rename-document',
        title: 'Rename this document',
        class: 'text-secondary',
        icon: PencilSquareIcon,
        onClick({ document }) {
          toRename = document;
        },
        visible(document) {
          return document.converted;
        },
      },
      {
        id: 'delete-document',
        title: 'Delete this document',
        class: 'text-destructive',
        icon: XCircleIcon,
        onClick({ document, index }) {
          toDelete = document;
        },
        visible(/* document */) {
          return true;
        },
      },
    ]"
    @select="fetchAndRenderDocument"
  >
  </FilesList>
  <p v-else class="text-sm text-foreground/60">
    You have not added any files. Best is to do it now.
  </p>
  <RenameDialog
    title="Rename File"
    :target="toRename"
    :submit="
      ({ id, name }) =>
        request({ type: 'POST', url: `/sources/${id}`, body: { name } })
    "
    @renamed="onRenamed"
    @cancelled="toRename = null"
  />
  <DeleteDialog
    :target="toDelete"
    :submit="deleteDocument"
    challenge="random"
    @cancelled="toDelete = null"
    @deleted="onDeleted"
  />
</template>

<script setup>
import {
  ArrowPathRoundedSquareIcon,
  CloudArrowUpIcon,
  DocumentArrowDownIcon,
  PlusIcon,
  XCircleIcon,
} from '@heroicons/vue/24/solid';
import { inject, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import FilesList from './FilesList.vue';
import Button from '../interactive/Button.vue';
import RenameDialog from '../../dialogs/RenameDialog.vue';
import CreateDialog from '../../dialogs/CreateDialog.vue';
import DeleteDialog from '../../dialogs/DeleteDialog.vue';
import WizardDialog from '../../dialogs/WizardDialog.vue';
import { flashMessage } from '../notification/flashMessage.js';
import { ensureFileExtension } from '../../utils/files/ensureFileExtension.js';
import { createBlob } from '../../utils/files/createBlob.js';
import { request } from '../../utils/http/BackendRequest.js';
import { useFiles } from './useFiles.js';
import { asyncTimeout } from '../../utils/asyncTimeout.js';
import { useEcho } from '../../collab/useEcho.js';

const emit = defineEmits(['fileSelected', 'documentDeleted']);
const props = defineProps({
  initialFile: {
    type: String,
  },
});

const documents = inject('sources');
const url = window.location.pathname;
const segments = url.split('/');
const projectId = segments[2]; // Assuming project id is the third segment in URL path
const audioIsUploading = ref(false);
const { downloadSource } = useFiles();

async function transcribeFile(audio) {
  audioIsUploading.value = true;

  const formData = new FormData();
  formData.append('file', audio);
  formData.append('project_id', projectId);
  formData.append('model', 'default_model'); // Replace with your actual model name
  formData.append('language', 'en'); // Replace with the desired language code

  try {
    const response = await axios.post('/files/transcribe', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data.newDocument) {
      response.data.newDocument.isConverting = true;
      response.data.newDocument.userPicture =
        usePage().props.auth.user.profile_photo_url;
      documents.push(response.data.newDocument);
      // fileSelected(response.data.newDocument);
    }
  } catch (error) {
    console.error('Error transcribing file:', error);
    flashMessage({
      message: 'An error occurred while transcribing the file.',
      type: 'error',
    });
  } finally {
    audioIsUploading.value = false;
  }
}

async function retryTranscription(document) {
  const url = `/projects/${projectId}/sources/${document.id}/retrytranscription`;
  try {
    const response = await axios.post(url);
    const { message, status } = response.data;

    if (status === 'finished') {
      document.isConverting = false;
      document.converted = true;
      document.failed = false;
    } else {
      document.isConverting = true;
      document.converted = false;
      document.failed = false;
    }

    flashMessage(message);
  } catch (error) {
    console.error('Error retrying conversion:', error);
    document.failed = true;
    flashMessage(
      error.response.data.message ||
        'An error occurred while converting the document.'
    );
  }
}

async function retryConvert(document) {
  const url = `/projects/${projectId}/sources/${document.id}/gethtmlcontent`;
  try {
    document.isConverting = true;
    const response = await axios.post(url);
    document.isConverting = !response.data.success;
    document.converted = true;
  } catch (error) {
    console.error('Error retrying conversion:', error);
    document.failed = true;
    flashMessage(
      error.response.data.message ||
        'An error occurred while converting the document.'
    );
  }
}

/*---------------------------------------------------------------------------*/
// CREATE DOCUMENT
/*---------------------------------------------------------------------------*/
const createSchema = ref(null);
const createNewFile = () => {
  // Generate a casual name for the file
  const fileName = `NewDocument_${new Date().getTime()}.txt`;
  createSchema.value = {
    name: {
      type: String,
      label: 'Filename',
      required: true,
      defaultValue: fileName,
    },
  };
};
const onCreateSubmit = async ({ name }) => {
  const fileName = ensureFileExtension(name, 'txt');
  const emptyFile = new File([createBlob()], fileName, { type: 'text/plain' });

  // Start the upload process
  isUploading.value = true;

  const formData = new FormData();
  formData.append('file', emptyFile);
  formData.append('projectId', usePage().props.projectId ?? 0);

  const response = await axios.post('/files/upload', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
  return response.data.newDocument;
};
const onCreated = (newDocument) => {
  if (newDocument) {
    documents.push(newDocument);
    fileSelected(newDocument);
  }
};
/*---------------------------------------------------------------------------*/
// IMPORT FILES
/*---------------------------------------------------------------------------*/
const importSchema = ref(null);
const isUploading = ref(false);
const uploadProgress = ref(0);

const importFiles = async (files) => {
  for (const file of files) {
    file.isUploading = true;
    documents.push(file);

    const newFile = file.type.startsWith('audio/')
      ? await transcribeFile(file)
      : await fileAdded(file);
    await asyncTimeout(100);
    const index = documents.indexOf(file);
    documents.splice(index, 1);
    documents.push(newFile);
    await asyncTimeout(1000);
  }

  flashMessage(`Uploaded ${files.length} files. Processing mike take a while. Feel free to reload the page.`)
};
/*---------------------------------------------------------------------------*/
// RENAME DOCUMENT
/*---------------------------------------------------------------------------*/
const toRename = ref(null);
const onRenamed = ({ id, name }) => {
  // Update the document's name in the documents array
  const documentIndex = documents.findIndex((doc) => doc.id === id);
  if (documentIndex !== -1) {
    documents[documentIndex].name = name;
  }

  toRename.value = null;
};

/*---------------------------------------------------------------------------*/
// DELETE DOCUMENT
/*---------------------------------------------------------------------------*/
const toDelete = ref(null);

async function deleteDocument(document, index) {
  const { response, error } = await request({
    url: `/files/${document.id}`,
    type: 'delete',
  });
  if (error) throw error;
  if (response.data.status >= 400) throw new Error(response.data.message);
  return document;
}

const onDeleted = ({ id, name }) => {
    debugger
  // Emit an event to inform the parent that a document has been deleted
  emit('documentDeleted', id);

  // Remove the document from the local state
  const index = documents.findIndex((doc) => doc.id === id);
  if (index > -1) {
      documents.splice(index, 1);
      flashMessage(`Deleted source: ${name}`)
  }
};

async function fileAdded(file) {
  const isRtf =
    file.type === 'text/rtf' || (file.name && file.name.endsWith('.rtf'));
  uploadProgress.value = 0;

  const formData = new FormData();
  formData.append('file', file);
  formData.append('projectId', usePage().props.projectId ?? 0);

  try {
    const response = await axios.post('/files/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onUploadProgress: (progressEvent) => {
        uploadProgress.value =
          (progressEvent.loaded / progressEvent.total) * 100;
      },
    });

    if (response.data.newDocument) {
      if (isRtf) {
        response.data.newDocument.isConverting = true;
      }
      response.data.newDocument.userPicture =
        usePage().props.auth.user.profile_photo_url;
      return response.data.newDocument;
    }
  } catch (error) {
    console.error('File upload failed:', error);
    let errMesg = `upload ${file.name} failed, ${error.response.data.message}`;
    if (error.response.status === 429) {
      errMesg = `Please wait a few minutes and try again. (${errMesg})`;
    }
    flashMessage(errMesg, { type: 'error' });
  } finally {
    isUploading.value = false; // Stop loading indicator
  }
}

onMounted(() => {
  const echo = useEcho().init();
  /**
   * Listen for conversion completed events
   * and update the local state accordingly
   */
  echo.private('conversion.' + projectId).listen('ConversionCompleted', (e) => {
    let documentIndex = -1;
    documents.forEach((doc, index) => {
      console.log(doc.id);
      if (doc.id === e.sourceId) {
        documentIndex = index;
      }
    });

    if (documentIndex !== -1) {
      documents[documentIndex].isConverting = false;
      documents[documentIndex].converted = true;
      documents[documentIndex].failed = false;
    }
  });
  echo.private('conversion.' + projectId).listen('ConversionFailed', (e) => {
    let documentIndex = -1;
    documents.forEach((doc, index) => {
      console.log(doc.id);
      if (doc.id === e.sourceId) {
        documentIndex = index;
      }
    });

    if (documentIndex !== -1) {
      documents[documentIndex].isConverting = false;
      documents[documentIndex].converted = false;
      documents[documentIndex].failed = true;
    }
  });
  window.addEventListener('beforeunload', (event) => {
    if (documents.some((document) => document.isConverting)) {
      // Custom confirmation message (return a string to modify the default)
      event.returnValue =
        'Are you sure you want to reload? A document is elaborating.';
    }
  });
});

watch(
  () => props.initialFile,
  (id) => {
    if (id) {
      fetchAndRenderDocument({ id, converted: true }).catch(console.error);
    }
  }
);

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', (event) => {
    if (documents.some((document) => document.isConverting)) {
      // Custom confirmation message (return a string to modify the default)
      event.returnValue =
        'Are you sure you want to reload? A document is elaborating.';
    }
  });
});

function fileSelected(file) {
  // Update 'selected' property for all documents
  for (let doc of documents) {
    doc.selected = doc.id === file.id;
  }

  const locked = file.isLocked;
  const hasSelections = file.selections && file.selections.length > 0;
  const CanUnlock = file.CanUnlock;
  const id = file.id;
  const name = file.name;
  const content = file.content;
  const date = file.date;
  const user = file.user;
  const type = file.type;

  file.selected = true;
  emit('fileSelected', {
    id,
    name,
    type,
    date,
    user,
    content,
    locked,
    CanUnlock,
    hasSelections,
  });
}

async function fetchAndRenderDocument(document) {
  if (document.isConverting || !document.converted) {
    return;
  }
  try {
    const response = await axios.get(`/files/${document.id}`);
    const fetchedDocument = response.data;
    // Call fileSelected with the fetched document
    fileSelected(fetchedDocument);
  } catch (error) {
    console.error('Failed to fetch document:', error);
  }
}
</script>
<style scoped>
@keyframes fillAnimation {
  0% {
    width: 0%;
  }
  100% {
    width: 100%;
  }
}

@keyframes shinyAnimation {
  0% {
    background-position: -50%;
  }
  100% {
    background-position: 150%;
  }
}

.combined-effect {
  position: relative;
  overflow: hidden;
  height: 4px;
  width: 0;
  animation:
    fillAnimation 2s linear forwards,
    shinyAnimation 1s linear infinite;
  background-image: linear-gradient(
    to right,
    transparent,
    rgba(255, 255, 255, 0.8),
    transparent
  );
  background-size: 50% 100%;
}
</style>
