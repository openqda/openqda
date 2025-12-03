<template>
  <AuthenticatedLayout :title="pageTitle" :menu="true" :showFooter="false">
    <template #menu>
      <BaseContainer>
        <ActivityIndicator v-if="!codingInitialized">
          Loading codes and selections...
        </ActivityIndicator>
        <div v-else class="inline md:flex items-center justify-between mb-4">
          <CreateDialog
            :schema="createNewCodeSchema"
            :title="`Create a new ${codesView === 'codes' ? 'Code' : 'Codebook'}`"
            :submit="createCodeHandler"
          >
            <template #trigger="createCodeTriggerProps">
              <Button
                variant="outline-secondary"
                class="w-full md:w-auto"
                @click="createCodeTriggerProps.onClick(openCreateCodeDialog)"
              >
                <PlusIcon class="w-4 h-4 me-1" />
                <span v-if="codesView === 'codes' && range?.length">
                  Create In-Vivo
                </span>
                <span v-else>Create</span>
              </Button>
            </template>
          </CreateDialog>
          <CreateDialog
            :title="`Edit ${editTarget?.name}`"
            :schema="editSchema"
            buttonTitle="Update code"
            :submit="updateCode"
            :show="!!editSchema"
          />
          <DeleteDialog
            :title="`Permanently delete ${deleteTarget?.name}`"
            :target="deleteTarget"
            :challenge="deleteChallenge"
            :message="deleteMessage"
            :submit="deleteCode"
          />
          <ResponsiveTabList
            :tabs="codesTabs"
            :initial="codesView"
            @change="(value) => (codesView = value)"
          />
        </div>
        <Cleanup v-if="codesView === 'cleanup'" />
        <CodeTree
          v-for="codebook in codebooks"
          :key="codebook.id"
          :codebook="codebook"
          :codes="codes.filter((code) => code.codebook === codebook.id)"
          v-if="codesView === 'codes'"
        />
        <FilesList
          v-if="codesView === 'sources'"
          :documents="sourceDocuments"
          :fixed="true"
          :focus-on-hover="true"
          :actions="[]"
          @select="switchFile"
        />
        <p
          v-if="codesView === 'sources' && !sourceDocuments?.length"
          class="p-3 text-foreground/60"
        >
          No other sources locked for coding. Go to
          <Link :href="route('source.index', projectId)"
            >the preparations page</Link
          >
          to edit and lock sources for coding.
        </p>
        <div class="mt-auto">
          <Footer />
        </div>
      </BaseContainer>
    </template>
    <template #main>
      <CodingEditor
        v-if="$props.source"
        :project="{ id: props.projectId }"
        :source="$props.source"
        :codes="$props.allCodes"
        class="overflow-y-auto overflow-x-hidden w-full h-full"
      />
      <div
        class="flex-col lg:flex items-center justify-center h-full text-foreground/50 p-2 md:p-4 lg:p-8"
        v-else
      >
        <div>
          <Headline2>Coding</Headline2>
          <div class="my-4 block">
            If you see this message, no source has been selected for coding. You
            can go to
            <Link :href="route('source.index', projectId)"
              >the preparations page</Link
            >
            to edit and lock sources for coding.
          </div>
          <HelpResources class="flex flex-col gap-4" />
        </div>
      </div>
    </template>
  </AuthenticatedLayout>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/24/solid';
import { onMounted, ref } from 'vue';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue';
import CodeTree from './coding/tree/CodeTree.vue';
import CodingEditor from './coding/CodingEditor.vue';
import BaseContainer from '../Layouts/BaseContainer.vue';
import ResponsiveTabList from '../Components/lists/ResponsiveTabList.vue';
import Button from '../Components/interactive/Button.vue';
import { useCodes } from '../domain/codes/useCodes.js';
import { useRange } from './coding/useRange.js';
import { useRenameDialog } from '../dialogs/useRenameDialog.js';
import { useDeleteDialog } from '../dialogs/useDeleteDialog.js';
import CreateDialog from '../dialogs/FormDialog.vue';
import DeleteDialog from '../dialogs/DeleteDialog.vue';
import { useSelections } from './coding/selections/useSelections.js';
import FilesList from '../Components/files/FilesList.vue';
import { router } from '@inertiajs/vue3';
import { asyncTimeout } from '../utils/asyncTimeout.js';
import ActivityIndicator from '../Components/ActivityIndicator.vue';
import { attemptAsync } from '../Components/notification/attemptAsync.js';
import { useCleanup } from './coding/cleanup/useCleanup.js';
import Cleanup from './coding/cleanup/Cleanup.vue';
import { flashMessage } from '../Components/notification/flashMessage.js';
import Footer from '../Layouts/Footer.vue';
import Link from '../Components/Link.vue';
import Headline2 from '../Components/layout/Headline2.vue';
import HelpResources from '../Components/HelpResources.vue';

const props = defineProps(['source', 'sources', 'allCodes', 'projectId']);
//------------------------------------------------------------------------
// SOURCES
//------------------------------------------------------------------------
const sourceDocuments = ref(
  props.sources
    .filter((source) => {
      if (source.id === props.source?.id) return false;
      if (source.isLocked) return true;
      return (source.variables ?? []).find(
        ({ name, boolean_value }) => name === 'isLocked' && boolean_value === 1
      );
    })
    .map((source) => {
      const copy = { ...source };
      copy.date = new Date(source.updated_at).toLocaleDateString();
      copy.variables = { isLocked: true };
      copy.isConverting = false;
      copy.failed = false;
      copy.converted = true;
      return copy;
    })
);
const switchFile = (file) => {
  router.get(route('source.code', file.id));
};
//------------------------------------------------------------------------
// GENERIC EDIT DIALOG
//------------------------------------------------------------------------
const { schema: editSchema, target: editTarget } = useRenameDialog();
const {
  target: deleteTarget,
  challenge: deleteChallenge,
  message: deleteMessage,
} = useDeleteDialog();

//------------------------------------------------------------------------
// RANGE / SELECTION
//------------------------------------------------------------------------
const { range, text, prevRange } = useRange();
const { createSelection } = useSelections();

//------------------------------------------------------------------------
// CODES / CODEBOOKS
//------------------------------------------------------------------------
const {
  codes,
  codebooks,
  createCode,
  createCodeSchema,
  updateCode,
  deleteCode,
  initCoding,
} = useCodes();
const codingInitialized = ref(false);
const codesTabs = [
  { value: 'codes', label: 'Codes' },
  { value: 'sources', label: 'Sources' },
  { value: 'cleanup', label: 'Cleanup' },
];

const codesView = ref(codesTabs[0].value);
const createNewCodeSchema = ref();
const openCreateCodeDialog = () => {
  createNewCodeSchema.value = createCodeSchema({
    title: text?.value,
    codebooks: codebooks.value,
  });
};
const createCodeHandler = async (formData) => {
  const code = await createCode(formData);
  const txt = createNewCodeSchema.value.title.defaultValue;
  const { index, length } = prevRange.value ?? {};

  // immediately apply in-vivo codes as selections
  if (code && txt && length) {
    try {
      await createSelection({
        code,
        text: txt,
        index,
        length,
      });
    } catch (e) {
      console.error(e);
    }
  }
  return code;
};

//------------------------------------------------------------------------
// CLEANUP
//------------------------------------------------------------------------
const CleanupCtx = useCleanup();

//------------------------------------------------------------------------
// PAGE
//------------------------------------------------------------------------
const pageTitle = ref('Coding');

onMounted(async () => {
  const fileId = new URLSearchParams(window.location.search).get('source');
  if (fileId !== props.source?.id) {
    // relocate?
  }

  if (props.source) {
    onSourceSelected(props.source);
    await asyncTimeout(100);
  }
  const result = await attemptAsync(() => initCoding());
  if (result?.clean?.length) {
    result.clean.forEach((entry) => CleanupCtx.add(entry));
    flashMessage('Unresolved references found. Please run cleanup.', {
      type: 'error',
    });
  }

  codingInitialized.value = true;
});

const onSourceSelected = (file) => {
  codingInitialized.value = false;
  const url = new URL(location.href);
  if (url.searchParams.get('source') !== file.id) {
    url.searchParams.set('source', file.id);
    history.pushState({ ...history.state }, '', url);
  }
  pageTitle.value = `Coding of ${file.name}`;
};
</script>

<style scoped>
.contextMenuOption:hover {
  opacity: 0.7;
}

.editor-container {
  display: flex;
  flex-direction: row;
}

/* Dropdown menu item styles */
[role='menuitem'] {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  cursor: pointer;
  transition: opacity 0.2s ease-in-out;
}

[role='menuitem']:hover {
  opacity: 0.7;
}

.hide-scrollbar::-webkit-scrollbar {
  display: none;
}

.hide-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

#dropzone {
  transform: translateY(0);
  transition: top 1.5s ease-in-out; /* Adjust timings if desired */
}

/** quill styles **/
/* Alignment Classes */

:deep(.ql-align-center) {
  text-align: center;
}

:deep(.ql-align-justify) {
  text-align: justify;
}

:deep(.ql-align-right) {
  text-align: right;
}

/* Formatting Classes */
:deep(.ql-bold) {
  font-weight: bold;
}

:deep(.ql-italic) {
  font-style: italic;
}

:deep(.ql-underline) {
  text-decoration: underline;
}

:deep(.ql-strike) {
  text-decoration: line-through;
}

:deep(.ql-code-block) {
  /* Your code block styles here */
}

:deep(.ql-blockquote) {
  /* Your blockquote styles here */
}

:deep(.ql-link) {
  /* Your link styles here */
}

/* List Classes */
:deep(.ql-list-ordered) {
  list-style-type: decimal;
}

:deep(.ql-list-bullet) {
  list-style-type: disc;
}

/* Text Size Classes */
:deep(.ql-size-small) {
  /* Smaller font size */
}

:deep(.ql-size-large) {
  /* Larger font size */
}

:deep(.ql-size-huge) {
  /* Even larger font */
}
</style>
