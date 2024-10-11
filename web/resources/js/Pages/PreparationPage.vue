<template>
  <AuthenticatedLayout :menu="true" :showFooter="false" :title="pageTitle">
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
                <span
                  v-if="saving"
                  class="text-xs inline-flex items-center border border-secondary rounded-lg p-3"
                >
                  <ArrowPathIcon class="w-4 h-4 me-1 text-secondary" />
                  saving
                </span>
                <span
                  v-if="saved"
                  class="text-xs inline-flex items-center border border-confirmative rounded-lg p-3"
                >
                  <CheckIcon class="w-4 h-4 me-1 text-confirmative" />
                  saved
                </span>
              </div>
            </template>
            <template #actions>
              <div class="flex items-center space-x-2 me-2">
                <Button
                  v-if="editorSourceRef.CanUnlock && !editorSourceRef.hasSelections"
                  variant="outline-secondary"
                  :icon="LockOpenIcon"
                  @click="toConfirm({
                    text: 'Are you sure you want to unlock the source? This will affect all codes and analysis, applied to this source.',
                    fn: unlockSource
                  })"
                  class="px-1 mx-3 rounded-xl"
                  >Unlock
                </Button>
                <Button
                  v-if="
                    !editorSourceRef.CanUnlock && !editorSourceRef.hasSelections
                  "
                  variant="outline-secondary"
                  :icon="LockClosedIcon"
                  @click="toConfirm({
                    text: 'Are you sure you want to lock the source and start coding?',
                    fn: lockAndCode
                  })"
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
                <ConfirmDialog
                  :text="confirm.text"
                  :show="!!confirm.text"
                  @confirmed="onConfirm"
                  @cancelled="toConfirm(null)"
                />
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
} from 'vue';
import PreparationsEditor from '../editor/PreparationsEditor.vue';
import FilesManager from '../Components/files/FilesManager.vue';
import Button from '../Components/interactive/Button.vue';
import {
  LockClosedIcon,
  LockOpenIcon,
  QrCodeIcon,
  ArrowPathIcon,
  CheckIcon,
} from '@heroicons/vue/20/solid';
import { router } from '@inertiajs/vue3';
import { flashMessage } from '../Components/notification/flashMessage.js';
import { request } from '../utils/http/BackendRequest.js';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue';
import { asyncTimeout } from '../utils/asyncTimeout.js';
import ConfirmDialog from '../dialogs/ConfirmDialog.vue';

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
const pageTitle = ref('Preparation')

watch(
  () => props.newDocument,
  (newValue) => {
    if (newValue !== null) {
      documents.value.push(newValue);
      loadFileIntoEditor(newValue);
    }
  }
);

/*---------------------------------------------------------------------------*/
// LOCK AND CODE
/*---------------------------------------------------------------------------*/
const confirm = ref({});
const toConfirm = (data) => {
    confirm.value = data ? data : {}
}
const onConfirm = async () => {
    await confirm.value.fn()
    toConfirm(null)
}

const lockAndCode = () => router.post(route('source.lock', editorSourceRef.value.id));

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

  const url = new URL(location.href);
  if (url.searchParams.get('file') !== file.id) {
    url.searchParams.set('file', file.id);
    history.pushState(history.state, '', url);
  }
  pageTitle.value = `Preparation: ${file.name}`;
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
  await asyncTimeout(300);
  saving.value = false;
  saved.value = true;
  setTimeout(() => {
    saved.value = false;
  }, 1000);
}

const initialFile = ref(null);
onMounted(() => {
  const fileId = new URLSearchParams(window.location.search).get('file');
  const file = fileId && props.sources.find((f) => f.id === fileId);
  initialFile.value = file?.id ?? null;
});

provide('sources', props.sources);
provide('newDocument', props.newDocument);
</script>
