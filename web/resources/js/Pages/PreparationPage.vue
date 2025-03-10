<template>
  <AuthenticatedLayout :menu="true" :showFooter="false" :title="pageTitle">
    <template #menu>
      <BaseContainer>
        <FilesManager
          :initialFile="initialFile"
          :project-id="props.projectId"
          @fileSelected="loadFileIntoEditor($event)"
          @documentDeleted="onDocumentDeleted"
        />
      </BaseContainer>
    </template>
    <template #main>
      <div ref="rightSide" class="overflow-auto w-full h-full">
        <div
          class="flex items-center justify-center h-full text-foreground/50"
          v-show="!editorSourceRef.selected"
        >
          <div>
            <Headline2>Preparation</Headline2>
            <div class="my-4 block">
              In order to code any sources you either create a new empty file or
              import existing ones.
            </div>
            <HelpResources class="space-y-4" />
          </div>
        </div>
        <div v-show="editorSourceRef.selected === true" class="mt-3">
          <PreparationsEditor
            ref="editorComponent"
            :source="editorSourceRef.content"
            :locked="editorSourceRef.locked"
            :CanUnlock="editorSourceRef.CanUnlock"
            @autosave="saveQuillContent"
          >
            <template #status>
              <div class="me-2 self-center">
                <span
                  v-if="saving"
                  class="text-xs inline-flex items-center p-1"
                >
                  <ArrowPathIcon class="w-4 h-4 me-1 text-secondary" />
                  saving
                </span>
                <span v-if="saved" class="text-xs inline-flex items-center p-1">
                  <CheckIcon class="w-4 h-4 me-1 text-confirmative" />
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
                  @click="
                    toConfirm({
                      text: 'Are you sure you want to unlock the source? This will affect all codes and analysis, applied to this source.',
                      fn: unlockSource,
                    })
                  "
                  class="px-1 mx-3 rounded-xl"
                  >Unlock
                </Button>
                <Button
                  v-if="
                    !editorSourceRef.CanUnlock && !editorSourceRef.hasSelections
                  "
                  variant="outline-secondary"
                  :icon="LockClosedIcon"
                  @click="
                    toConfirm({
                      text: 'Are you sure you want to lock the source and start coding?',
                      fn: lockAndCode,
                    })
                  "
                  class="px-1 py-2 mx-3 rounded-xl"
                  >Lock for coding
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
                  :show-confirm="true"
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
import { defineProps, onMounted, provide, ref, watch } from 'vue';
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
import BaseContainer from '../Layouts/BaseContainer.vue';
import Headline2 from '../Components/layout/Headline2.vue';
import HelpResources from '../Components/HelpResources.vue';

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

const editorComponent = ref();
const props = defineProps(['sources', 'newDocument', 'projectId']);
const documents = ref([]);
const pageTitle = ref('Preparation');

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
  confirm.value = data ? data : {};
};
const onConfirm = async () => {
  await confirm.value.fn();
  toConfirm(null);
};

const lockAndCode = () =>
  router.post(route('source.code', editorSourceRef.value.id));
const codeThisFile = () =>
  router.get(route('source.code', editorSourceRef.value.id));

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
    flashMessage(msg, { type: 'error' });
  }
};

/*---------------------------------------------------------------------------*/
// EDITING
/*---------------------------------------------------------------------------*/
function loadFileIntoEditor(source) {
  if (!source?.content) {
    return;
  }
  editorSourceRef.value.content = source.content;
  editorSourceRef.value.selected = true;
  editorSourceRef.value.id = source.id;
  editorSourceRef.value.name = source.name;
  editorSourceRef.value.locked = source.locked;
  editorSourceRef.value.CanUnlock = source.CanUnlock;
  editorSourceRef.value.hasSelections = source.hasSelections;
  editorSourceRef.value.showLineNumbers = source.showLineNumbers ?? false;
  editorSourceRef.value.charsXLine = source.charsXLine;

  const url = new URL(location.href);
  if (url.searchParams.get('file') !== source.id) {
    url.searchParams.set('file', source.id);
    history.pushState(history.state, '', url);
  }
  pageTitle.value = `Preparation: ${source.name}`;
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

async function saveQuillContent(html) {
  saving.value = true;
  const { response, error } = await request({
    url: '/source/update',
    type: 'post',
    body: {
      id: editorSourceRef.value.id,
      content: { editorContent: html },
    },
  });

  if (error || response.status >= 400) {
    console.error('An error occurred while saving:', error);
    let message = error ? error.response.data.message : response.data.message;
    if (message.includes('malicious')) {
      message += ' Try to paste without formatting.';
    }
    await flashMessage(message, { type: 'error' });
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

provide('sources', props.sources ?? []);
provide('newDocument', props.newDocument);
</script>
