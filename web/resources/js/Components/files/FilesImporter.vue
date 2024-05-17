<template>
  <div
    v-if="isRenaming"
    class="fixed z-50 inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex justify-center items-center"
  >
    <div
      class="bg-white p-8 md:p-12 lg:p-16 rounded-lg shadow-lg w-full md:w-3/4 lg:w-1/2 xl:w-1/3 z-50"
    >
      <h3 class="font-semibold text-lg mb-4">Rename Document</h3>
      <div
        v-if="renameError"
        class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4"
      >
        {{ renameError }}
      </div>
      <input
        v-model="newName"
        class="border border-gray-300 p-2 rounded w-full mb-4"
        placeholder="Enter new name"
      />
      <div class="flex justify-end space-x-2">
        <button
          @click="submitRename"
          class="bg-cerulean-700 hover:bg-cerulean-700 text-white font-bold py-2 px-4 rounded"
        >
          Rename
        </button>
        <button
          @click="cancelRename"
          class="bg-gray-300 hover:bg-gray-400 text-black py-2 px-4 rounded"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
  <div class="flex items-center justify-start space-x-2 pl-2">
    <form>
      <FileUploadButton
        color="cerulean"
        @fileAdded="fileAdded"
        :icon="CloudArrowUpIcon"
        class="py-3"
        accept=".txt,.rtf"
        label="Import"
        fileSizeLimit="20"
      />
    </form>
    <label>
      <input @click="createNewFile" ref="fileCreate" class="hidden" />
      <span
        :class="`space-x-2 rounded bg-cerulean-700 px-2 py-1 text-xs font-semibold text-white shadow-sm hover:bg-cerulean-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-700 inline-flex items-center`"
      >
        <DocumentPlusIcon class="h-4 w-4 text-white"></DocumentPlusIcon>
        <span>New file</span>
      </span>
    </label>
  </div>
  <div
    v-if="isUploading"
    class="h-1 w-full bg-cerulean-700 py-3 my-2 relative top-0 combined-effect flex items-center justify-center text-white text-sm"
  >
    Processing the file...
    <!-- You can style this text further or add additional elements -->
  </div>
  <FilesList
    rowClass="px-2"
    :documents="documents"
    :actions="[
      {
        id: 'retry-conversion',
        title: 'Retry elaboration',
        icon: CloudArrowUpIcon,
        class: 'text-black-500 hover:text-cerulean-700',
        onClick({ action, document, index }) {
          retryConvert(document);
        },
      },
      {
        id: 'rename-document',
        title: 'Rename this document',
        icon: PencilSquareIcon,
        class: ' text-black hover:text-gray-600',
        onClick({ action, document, index }) {
          renameDocument(document, index);
        },
      },
      {
        id: 'delete-document',
        title: 'Delete this document',
        icon: XCircleIcon,
        class: ' text-red-700 hover:text-red-600',
        onClick({ action, document, index }) {
          deleteDocument(document, index);
        },
      },
    ]"
    @select="fetchAndRenderDocument"
  />
</template>

<script setup>
import {
  CloudArrowUpIcon,
  DocumentPlusIcon,
  PencilSquareIcon,
  XCircleIcon,
} from '@heroicons/vue/20/solid';
import FileUploadButton from '../interactive/FileUploadButton.vue';
import { inject, onBeforeUnmount, onMounted, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import FilesList from './FilesList.vue';

useForm({ file: null });
const emit = defineEmits(['fileSelected', 'documentDeleted']);
const documents = inject('sources');
const isUploading = ref(false);
const isRenaming = ref(false);
const newName = ref('');
let renamingDocumentId = null;
const renameError = ref('');
const url = window.location.pathname;
const segments = url.split('/');
const projectId = segments[2]; // Assuming project id is the third segment in URL path

function renameDocument(document /*, index */) {
  isRenaming.value = true;
  renamingDocumentId = document.id;
  newName.value = document.name;
}

async function retryConvert(document) {
  try {
    const response = await axios.post(
      `/projects/${projectId}/sources/${document.id}/gethtmlcontent`
    );

    document.isConverting = !response.data.success;
    document.converted = true;
  } catch (error) {
    console.error('Error renaming document:', error);
    renameError.value =
      error.response.data.message ||
      'An error occurred while converting the document.';
  }
}

async function submitRename() {
  if (renamingDocumentId && newName.value) {
    try {
      await axios.post(`/sources/${renamingDocumentId}`, {
        name: newName.value,
      });

      // Update the document's name in the documents array
      const documentIndex = documents.findIndex(
        (doc) => doc.id === renamingDocumentId
      );
      if (documentIndex !== -1) {
        documents[documentIndex].name = newName.value;
      }

      isRenaming.value = false;
      renameError.value = ''; // Clear any previous error
    } catch (error) {
      console.error('Error renaming document:', error);
      renameError.value =
        error.response.data.message ||
        'An error occurred while renaming the document.';
    }
  } else {
    if (newName.value.length === 0) {
      renameError.value = 'Please enter a name for the document.';
    }
  }
}

function cancelRename() {
  isRenaming.value = false;
  renameError.value = ''; // Clear any previous error
}

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
      usePage().props.flash.message = response.data.message;
    } else {
      console.error('Failed to delete the document:', response.data.message);
      usePage().props.flash.message = response.data.message;
    }
  } catch (error) {
    console.error('An error occurred while deleting the document:', error);
    usePage().props.flash.message =
      'An error occurred while deleting the document.';
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
      usePage().props.flash.message =
        error.response.data.message +
        ' Please wait a few minutes and try again.';
    }
  } finally {
    isUploading.value = false; // Stop loading indicator
  }
}

async function createNewFile() {
  // Generate a casual name for the file
  const fileName = `NewDocument_${new Date().getTime()}.txt`;

  const keyword =
    'Llanfair­pwllgwyngyll­gogery­chwyrn­drobwll­llan­tysilio­gogo­goch';
  // Create a Blob representing an empty file
  const emptyFileContent = new Blob([keyword], { type: 'text/plain' });
  const emptyFile = new File([emptyFileContent], fileName, {
    type: 'text/plain',
  });

  // Start the upload process
  isUploading.value = true;

  const formData = new FormData();
  formData.append('file', emptyFile);
  formData.append('projectId', usePage().props.projectId ?? 0);

  try {
    const response = await axios.post('/files/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    if (response.data.newDocument) {
      documents.push(response.data.newDocument);
      fileSelected(response.data.newDocument);
    }
  } catch (error) {
    console.error('File creation and upload failed:', error);
    if (error.response.status === 429) {
      usePage().props.flash.message =
        error.response.data.message +
        ' Please wait a few minutes and try again.';
    }
  } finally {
    isUploading.value = false;
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
  if (document.isConverting || !document.converted) return;
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
