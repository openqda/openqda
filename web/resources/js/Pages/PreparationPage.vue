<template>
  <AuthenticatedLayout :menu="true" :showFooter="false">
    <template #menu>
      <FilesManager
        :initialFile="initialFile"
        @fileSelected="loadFileIntoEditor($event)"
        @documentDeleted="onDocumentDeleted"
      />
    </template>
    <template #main>
      <div ref="rightSide" class="overflow-auto w-full h-full">
        <div v-show="editorSourceRef.selected === true" class="mt-3">
          <PreparationsEditor
            ref="editorComponent"
            :source="editorSourceRef.content"
            :locked="editorSourceRef.locked"
            :CanUnlock="editorSourceRef.CanUnlock"
            @autosave="saveQuillContent"
          >
              <template #status>
                  <div class="w-10 mr-2 self-center">
                      <span v-if="saving" class="text-xs inline-flex items-center">
                          <ArrowPathIcon class="w-4 h-4 text-secondary" />
                          saving
                      </span>
                      <span v-if="saved" class="text-xs inline-flex items-center">
                          <CheckIcon class="w-4 h-4 text-confirmative" />
                          saved
                      </span>
                  </div>
              </template>
              <template #actions>
              <div class="flex items-center space-x-2 me-2">
                <Button
                  v-if="
                    editorSourceRef.CanUnlock && !editorSourceRef.hasSelections
                  "
                  variant="outline-secondary"
                  :icon="LockOpenIcon"
                  @click="unlockSource"
                  class="px-1 mx-3 rounded-xl"
                  >Unlock
                </Button>
                <Button
                  v-if="
                    !editorSourceRef.CanUnlock && !editorSourceRef.hasSelections
                  "
                  variant="outline-secondary"
                  :icon="LockClosedIcon"
                  @click="lockAndCode"
                  class="px-1 py-2 mx-3 rounded-xl"
                  >Lock
                </Button>
                <Button
                  v-if="
                    editorSourceRef.CanUnlock ||
                    (!editorSourceRef.CanUnlock &&
                      editorSourceRef.hasSelections)
                  "
                  type="outline-secondary"
                  :icon="QrCodeIcon"
                  @click="codeThisFile"
                  class="px-1"
                  >Code
                </Button>
              </div>
            </template>
          </PreparationsEditor>
        </div>
      </div>
    </template>
  </AuthenticatedLayout>
</template>

<script setup>
import {
    computed,
    defineProps,
    onBeforeUnmount,
    onMounted,
    provide,
    ref,
    unref,
    watch,
} from 'vue'
import PreparationsEditor from '../Components/preparation/PreparationsEditor.vue';
import FilesManager from '../Components/files/FilesManager.vue';
import Button from '../Components/interactive/Button.vue';
import {
  LockClosedIcon,
  LockOpenIcon,
  QrCodeIcon,
    ArrowPathIcon,
    CheckIcon
} from '@heroicons/vue/20/solid';
import { router } from '@inertiajs/vue3';
import { debounce } from '../utils/dom/debounce.js';
import { flashMessage } from '../Components/notification/flashMessage.js';
import { request } from '../utils/http/BackendRequest.js';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue';
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { asyncTimeout } from '../utils/asyncTimeout.js'

const editorSourceRef = ref({
  content: 'select to display',
  selected: false,
  id: '',
  name: '',
  locked: true,
  CanUnlock: false,
  showLineNumbers: false,
  charsXLine: 80,
});

const focus = ref(false);
const editorComponent = ref();
const props = defineProps(['sources', 'newDocument']);
const documents = ref([]);

const initialWidth = Math.round(
  Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0) /
    2
);

const leftSide = ref(null);
const rightSide = ref(null);
const separator = ref(null);

const leftWidth = ref(
  Number(sessionStorage.getItem('preparation/leftWidth') || initialWidth)
);
const rightWidth = ref(
  Number(sessionStorage.getItem('preparation/rightWidth') || initialWidth)
); // remaining width
let isResizing = false;

const onMouseDown = () => {
  isResizing = true;
  const innerDiv = separator.value.querySelector('div'); // Select the inner div
  if (innerDiv) {
    innerDiv.classList.add('bg-cerulean-700'); // Add class on mouse down
    innerDiv.classList.remove('bg-gray-200'); // Add class on mouse down
  }
  document.addEventListener('mousemove', onMouseMove);
  document.addEventListener('mouseup', onMouseUp);
};

const onMouseMove = (e) => {
  if (!isResizing) {
    return;
  }
  const newLeftWidth = e.clientX;
  const newRightWidth = window.innerWidth - e.clientX;
  updateSeparator(newLeftWidth, newRightWidth);
};

const updateSeparator = (newLeftWidth, newRightWidth) => {
  if (Number.isNaN(newLeftWidth) || Number.isNaN(newRightWidth)) {
    return;
  }
  leftWidth.value = Math.round(newLeftWidth);
  rightWidth.value = Math.round(newRightWidth);
  sessionStorage.setItem('preparation/leftWidth', newLeftWidth); // Save to local storage
  sessionStorage.setItem('preparation/rightWidth', newRightWidth); // Save to local storage
};

const onMouseUp = () => {
  isResizing = false;
  const innerDiv = separator.value.querySelector('div'); // Select the inner div
  if (innerDiv) {
    innerDiv.classList.remove('bg-cerulean-700'); // Add class on mouse down
    innerDiv.classList.add('bg-gray-200'); // Add class on mouse down
  }
  document.removeEventListener('mousemove', onMouseMove);
  document.removeEventListener('mouseup', onMouseUp);
};

watch(
  () => props.newDocument,
  (newValue) => {
    if (newValue !== null) {
      documents.value.push(newValue);
      loadFileIntoEditor(newValue);
    }
  }
);

const lockAndCode = async () => {
  if (
    !confirm(
      'Are you sure you want to lock the document and start coding? It cannot be unlocked once you started coding.'
    )
  ) {
    return;
  }
  router.post(route('source.lock', editorSourceRef.value.id));
};

function codeThisFile() {
  router.get(route('source.go-and-code', editorSourceRef.value.id));
}

const unlockSource = async () => {
  const { response, error } = await request({
    url: `/sources/${editorSourceRef.value.id}/unlock`,
    type: 'post',
  });

  if (error) {
    console.error('An error occurred:', error);
    flashMessage(error.response.data.message, { type: 'error' });
  } else if (response.data.success) {
    editorSourceRef.value.locked = false;
    editorSourceRef.value.CanUnlock = false;
    flashMessage(response.data.message);
  } else {
    const msg = `Failed to unlock the source. (${response.data.message})`;
    console.error(msg);
    flashMessage(msg, { type: 'error' });
  }
};

function loadFileIntoEditor(file) {
  if (!file?.content) {
    return;
  }

  editorSourceRef.value.content = file.content;
  editorSourceRef.value.selected = true;
  editorSourceRef.value.id = file.id;
  editorSourceRef.value.name = file.name;
  editorSourceRef.value.locked = file.locked;
  editorSourceRef.value.CanUnlock = file.CanUnlock;
  editorSourceRef.value.hasSelections = file.hasSelections;
  editorSourceRef.value.showLineNumbers = file.showLineNumbers ?? false;
  editorSourceRef.value.charsXLine = file.charsXLine;

  const url = new URL(window.location);
  url.searchParams.set("file", file.id);
  history.pushState({}, "", url);
}

// Parent Component Script
function onDocumentDeleted(deletedDocumentId) {
  if (editorSourceRef.value.id === deletedDocumentId) {
    editorSourceRef.value.selected = false;
    editorSourceRef.value.content = ''; // Clear the editor content
    editorSourceRef.value.id = null; // Clear the editor ID
  }
}

const saving = ref(false);
const saved = ref(false);

async function saveQuillContent() {
  saving.value = true;
  const { response, error } = await request({
    url: '/source/update',
    type: 'post',
    body: {
      id: editorSourceRef.value.id,
      content: unref(editorComponent.value),
    },
  });

  if (error) {
    console.error('An error occurred while saving:', error);
    flashMessage(error.response.data.message, { type: 'error' });
  }
  await asyncTimeout(300)
  saving.value = false;
  saved.value = true
    setTimeout(() => {
        saved.value = false
    }, 1000)
}

const initialFile = ref(null)
onMounted(() => {
   const fileId = new URLSearchParams(window.location.search).get('file');
   const file = fileId && props.sources.find(f => f.id === fileId)
   initialFile.value = file?.id ?? null
    console.debug('initial file', initialFile.value )
});

provide('sources', props.sources);
provide('newDocument', props.newDocument);
</script>
