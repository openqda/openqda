<script setup>
/*-----------------------------------------------------------------------------
 | The Files Manager is the high-level main component, specifically designed
 | for the preparations process.
 | It's role is to connect files list with upload, update and remove
 | capabilities.
 *----------------------------------------------------------------------------*/
import {
  ArrowPathRoundedSquareIcon,
  CloudArrowUpIcon,
  DocumentArrowDownIcon,
  PlusIcon,
} from '@heroicons/vue/24/solid';
import { inject, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
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
import { useEcho } from '../../collab/useEcho.js';
import { PencilSquareIcon, XCircleIcon } from '@heroicons/vue/24/outline';

/*---------------------------------------------------------------------------*/
// DATA / PROPS
/*---------------------------------------------------------------------------*/
const emit = defineEmits(['fileSelected', 'documentDeleted']);
const props = defineProps({
  initialFile: {
    type: String,
  },
  projectId: String,
});

const { projectId } = props;
const allSources = inject('sources');
const documents = reactive(allSources);
const { downloadSource, queueFilesForUpload } = useFiles({ projectId });

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

const importFiles = (files) => {
  queueFilesForUpload({
    files,
    onError: (e) => {
      flashMessage(`An error occurred while uploading: ${e.message}`, {
        type: 'error',
      });
    },
  });
  flashMessage(`Added ${files.length} files for upload.`);
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

async function deleteDocument(document) {
  const { response, error } = await request({
    url: `/files/${document.id}`,
    type: 'delete',
  });
  if (error) throw error;
  if (response.data.status >= 400) throw new Error(response.data.message);
  return document;
}

const onDeleted = ({ id, name }) => {
  // Emit an event to inform the parent that a document has been deleted
  emit('documentDeleted', id);

  // Remove the document from the local state
  const index = documents.findIndex((doc) => doc.id === id);
  if (index > -1) {
    documents.splice(index, 1);
    flashMessage(`Deleted source: ${name}`);
  }
};

/*---------------------------------------------------------------------------*/
// LIFECYCLE
/*---------------------------------------------------------------------------*/
const channelid = `conversion.${projectId}`;
const echo = useEcho().init();

onMounted(() => {
  /**
   * Listen for conversion completed events
   * and update the local state accordingly
   */
  echo.private(channelid).listen('ConversionCompleted', (e) => {
    console.debug('ConversionCompleted', e);
    let documentIndex = -1;
    documents.forEach((doc, index) => {
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
  echo.private(channelid).listen('ConversionFailed', (e) => {
    console.debug('ConversionFailed', e);
    let documentIndex = -1;
    documents.forEach((doc, index) => {
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
});

onUnmounted(() => {
  echo.leave(channelid);
});

watch(
  () => props.initialFile,
  (id) => {
    if (id) {
      fetchAndRenderDocument({ id, converted: true }).catch(console.error);
    }
  }
);

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
    :progress="uploadProgress"
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
        title: 'Retry conversion',
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
