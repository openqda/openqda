<template>
  <div class="flex items-center justify-start mt-4">
    <Button
      variant="outline-secondary"
      class="rounded-xl"
      @click="createNewFile"
    >
      <PlusIcon class="h-4 w-4 mr-2"></PlusIcon>
      <span>New file</span>
    </Button>
    <Button
      variant="outline-secondary"
      class="rounded-xl ml-3"
      @click="importFile"
    >
      <PlusIcon class="h-4 w-4 mr-2"></PlusIcon>
      <span>Import</span>
    </Button>
    <!--
              <form>
                <FileUploadButton
                  color="cerulean"
                  @fileAdded="fileAdded"
                  :icon="CloudArrowUpIcon"
                  class="py-3"
                  accept=".txt,.rtf"
                  label="Import"
                  fileSizeLimit="200"
                />
              </form>
              <form @submit.prevent="transcribeFile">
                <FileUploadButton
                  color="cerulean"
                  @fileAdded="handleFileAdded"
                  :icon="CloudArrowUpIcon"
                  label="Transcribe audio"
                  accept="audio/*"
                  fileSizeLimit="100"
                />
              </form>
              -->
  </div>
  <CreateDialog
    :schema="createSchema"
    title="Create new file"
    :submit="onCreateSubmit"
    @created="onCreated"
    @cancelled="createSchema = null"
  />
  <FilesList
    class="mt-5"
    rowClass="px-2"
    :documents="documents"
    :actions="[
      {
        id: 'retry-atrain',
        title: 'Retry transcription',
        icon: ArrowPathRoundedSquareIcon,
        class: 'text-black-500 hover:text-cerulean-700',
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
        class: 'text-black-500 hover:text-cerulean-700',
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
        class: 'text-black-500 hover:text-cerulean-700',
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
        icon: PencilSquareIcon,
        class: ' text-black hover:text-gray-600',
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
        icon: XCircleIcon,
        class: ' text-red-700 hover:text-red-600',
        onClick({ document, index }) {
          toDelete = document
        },
        visible(/* document */) {
          return true;
        },
      },
    ]"
    @select="fetchAndRenderDocument"
  />
  <RenameDialog
    title="Rename File"
    :target="toRename"
    :submit="({ id, name }) => request({ type: 'POST', url: `/sources/${id}`, body: { name } })"
    @renamed="onRenamed"
    @cancelled="toRename = null"
  />
    <DeleteDialog
        :target="toDelete"
        :submit="deleteDocument"
        challenge="random"
        @cancelled="toDelete = null"
        />
</template>

<script setup>
import {
  ArrowPathRoundedSquareIcon,
  CloudArrowUpIcon,
  DocumentArrowDownIcon,
  PlusIcon,
  PencilSquareIcon,
  XCircleIcon,
} from '@heroicons/vue/24/solid';
import { inject, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import FilesList from './FilesList.vue';
import { flashMessage } from '../notification/flashMessage.js';
import Button from '../interactive/Button.vue';
import RenameDialog from '../../dialogs/RenameDialog.vue';
import CreateDialog from '../../dialogs/CreateDialog.vue';
import { ensureFileExtension } from '../../utils/files/ensureFileExtension.js';
import { createBlob } from '../../utils/files/createBlob.js';
import DeleteDialog from '../../dialogs/DeleteDialog.vue'
import { request } from '../../utils/http/BackendRequest.js'

useForm({ file: null });
const emit = defineEmits(['fileSelected', 'documentDeleted']);
const props = defineProps({
  initialFile: {
    type: String,
  },
});
const documents = inject('sources');
const isUploading = ref(false);
let renamingDocumentId = null;
const url = window.location.pathname;
const segments = url.split('/');
const projectId = segments[2]; // Assuming project id is the third segment in URL path

const audioFile = ref(null);
const audioIsUploading = ref(false);

function handleFileAdded({ files }) {
  audioFile.value = files[0];
  transcribeFile();
}

async function downloadSource(source) {
  try {
    // Perform the GET request to download the file
    const response = await axios({
      url: `/sources/${source.id}/download`,
      method: 'POST',
      responseType: 'blob', // Important to set response type to blob for binary data
    });

    // Extract the filename from the Content-Disposition header
    const disposition = response.headers['content-disposition'];
    let filename = source.name; // Fallback filename
    if (disposition && disposition.includes('attachment')) {
      const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
      const matches = filenameRegex.exec(disposition);
      if (matches != null && matches[1]) {
        filename = matches[1].replace(/['"]/g, ''); // Clean up the filename
      }
    }

    // Create a URL for the blob response data
    const url = window.URL.createObjectURL(createBlob({ data: response.data }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename); // Set the download attribute with the filename
    document.body.appendChild(link);
    link.click(); // Trigger the download

    // Clean up and remove the link from the DOM
    link.parentNode.removeChild(link);
  } catch (error) {
    console.error('Error downloading source file:', error);
    alert('An error occurred while downloading the source file.');
  }
}

async function transcribeFile() {
  if (!audioFile.value) {
    alert('Please select an audio file to transcribe.');
    return;
  }

  audioIsUploading.value = true;

  const formData = new FormData();
  formData.append('file', audioFile.value);
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
      fileSelected(response.data.newDocument);
    }
  } catch (error) {
    console.error('Error transcribing file:', error);
    alert('An error occurred while transcribing the file.');
  } finally {
    audioIsUploading.value = false;
  }
}

async function retryTranscription(document) {
  const url = `/projects/${projectId}/sources/${document.id}/retrytranscription`;
  try {
    const response = await axios.post(url);
    console.debug(response);
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
    console.debug(response);
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
const toDelete = ref(null)
async function deleteDocument(document, index) {
  // Confirmation dialogue
  if (!confirm('Are you sure you want to delete this document?')) {
    return;
  }

  try {
    const response = await axios.delete(`/files/${document.id}`);

    if (response.data.success) {
      // Emit an event to inform the parent that a document has been deleted
      emit('documentDeleted', document.id);

      // Remove the document from the local state
      documents.splice(index, 1);

      // Set the flash message
      flashMessage(response.data.message);
    } else {
      console.error('Failed to delete the document:', response.data.message);
      flashMessage(response.data.message);
    }
  } catch (error) {
    console.error('An error occurred while deleting the document:', error);
    flashMessage('An error occurred while deleting the document.');
  }
}

async function fileAdded({ files }) {
  const file = files[0];
  if (!file) {
    return;
  }

  const isRtf =
    file.type === 'text/rtf' || (file.name && file.name.endsWith('.rtf'));
  isUploading.value = true; // Start loading indicator

  const formData = new FormData();
  formData.append('file', file);
  formData.append('projectId', usePage().props.projectId ?? 0);

  try {
    const response = await axios.post('/files/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data.newDocument) {
      if (isRtf) {
        response.data.newDocument.isConverting = true;
      }
      response.data.newDocument.userPicture =
        usePage().props.auth.user.profile_photo_url;
      documents.push(response.data.newDocument);
      fileSelected(response.data.newDocument);
    }
  } catch (error) {
    console.error('File upload failed:', error);
    if (error.response.status === 429) {
      flashMessage(
        error.response.data.message +
          ' Please wait a few minutes and try again.'
      );
    }
  } finally {
    isUploading.value = false; // Stop loading indicator
  }
}

onMounted(() => {
  /**
   * Listen for conversion completed events
   * and update the local state accordingly
   */
  window.Echo.private('conversion.' + projectId).listen(
    'ConversionCompleted',
    (e) => {
      console.debug('ConversionCompleted', e.sourceId);
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
    }
  );
  window.Echo.private('conversion.' + projectId).listen(
    'ConversionFailed',
    (e) => {
      console.debug('ConversionFailed', e.sourceId, e.message);
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
    }
  );
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
