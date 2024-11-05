<template>
  <AuthenticatedLayout :title="pageTitle" :menu="true" :showFooter="false">
    <template #menu>
      <BaseContainer>
        <div class="flex items-baseline justify-between">
          <Button
            variant="outline-secondary"
            @click="openCreateDialog(codesView)"
          >
            <PlusIcon class="w-4 h-4 me-1" />
            <span v-if="codesView === 'codes' && range?.length"
              >Create In-Vivo</span
            >
            <span v-else>Create</span>
          </Button>
          <CreateDialog
            :schema="createSchema"
            :title="`Create a new ${codesView === 'codes' ? 'Code' : 'Codebook'}`"
            :submit="onCreateSubmit"
            @created="onCreated"
            @cancelled="createSchema = null"
          />
          <ResponsiveTabList
            :tabs="codesTabs"
            :initial="codesView"
            @change="(value) => (codesView = value)"
          />
        </div>
        <div
          class="flex justify-between text-foreground/60"
          v-if="codesView === 'codes'"
        >
          <p class="my-0 text-xs">
            {{
              range?.length
                ? 'Click a code to assign to selection'
                : 'Manage codes'
            }}
          </p>
          <div class="text-xs">Sort by name</div>
        </div>
        <CodeList
          v-for="codebook in codebooks"
          :codebook="codebook"
          v-if="codesView === 'codes'"
        />
        <CodebookList v-if="codesView === 'codebooks'" />
      </BaseContainer>
    </template>
    <template #main>
      <CodingEditor
        :project="{ id: projectId }"
        :source="$props.source"
        :codes="$props.allCodes"
      />
    </template>
  </AuthenticatedLayout>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/24/solid';
import { onMounted, ref } from 'vue';
import AuthenticatedLayout from '../Layouts/AuthenticatedLayout.vue';
import CodeList from './coding/CodeList.vue';
import CodingEditor from './coding/CodingEditor.vue';
import BaseContainer from '../Layouts/BaseContainer.vue';
import { useCodes } from './coding/useCodes.js';
import ResponsiveTabList from '../Components/lists/ResponsiveTabList.vue';
import CodebookList from './coding/CodebookList.vue';
import Button from '../Components/interactive/Button.vue';
import { useRange } from './coding/useRange.js';
import CreateDialog from '../dialogs/CreateDialog.vue';
import { Codes } from './coding/codes/Codes.js';
import { randomColor } from '../utils/random/randomColor.js';

const props = defineProps([
  'source',
  'sources',
  'codebooks',
  'allCodes',
  'projectId',
]);

//------------------------------------------------------------------------
// RANGE / SELECTION
//------------------------------------------------------------------------
const { range } = useRange();

//------------------------------------------------------------------------
// CODES / CODEBOOKS
//------------------------------------------------------------------------
const { codebooks, initCodebooks } = useCodes();
const { text } = useRange();
const codesTabs = [
  { value: 'codes', label: 'Codes' },
  { value: 'codebooks', label: 'Codebooks' },
];
const codesView = ref(codesTabs[0].value);
const createSchema = ref(null);
const openCreateDialog = (view) => {
  if (view === 'codes') {
    createSchema.value = {
      title: {
        type: String,
        placeholder: 'Name of the code',
        defaultValue: text.value,
      },
      description: {
        type: String,
        placeholder: 'Code description, optional',
      },
      color: {
        type: String,
        formType: 'color',
        defaultValue: randomColor({ type: 'hex' }),
      },
      codebookId: {
        type: Number,
        label: 'Codebook',
        defaultValue: codebooks.value?.[0]?.id,
        options: codebooks.value.map((c) => ({
          value: c.id,
          label: c.name,
        })),
      },
    };
  }
};
const onCreateSubmit = async (options) => {
  ['title', 'color', 'codebookId'].forEach((key) => {
    if (!options[key]) {
      throw new Error(`${key} is required!`);
    }
  });
  const { error } = await Codes.create({ projectId, ...options });
  if (error) throw error;
};
const onCreated = () => {};

//------------------------------------------------------------------------
// PAGE
//------------------------------------------------------------------------
const pageTitle = ref('Coding');
const url = window.location.pathname;
const segments = url.split('/');
const projectId = segments[2]; // Assuming project id is the third segment in URL path

onMounted(async () => {
  const fileId = new URLSearchParams(window.location.search).get('source');
  if (fileId !== props.source.id) {
    // relocate?
  }
  onSourceSelected(props.source);

  await initCodebooks();
});

const onSourceSelected = (file) => {
  const url = new URL(location.href);
  if (url.searchParams.get('source') !== file.id) {
    url.searchParams.set('source', file.id);
    history.pushState(history.state, '', url);
  }
  pageTitle.value = `Coding: ${file.name}`;
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

<style lang="postcss" scoped>
#editor :deep(h1) {
  @apply text-2xl;
}

#editor :deep(h2) {
  @apply text-xl;
}

#editor :deep(h3) {
  @apply text-lg;
}

/*
  Enter and leave animations can use different
  durations and timing functions.
*/
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
  transition: all 0.8s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>
