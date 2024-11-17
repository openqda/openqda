<script setup lang="ts">
import { computed, inject, ref } from 'vue';
import CodebookItem from './CodebookItem.vue';
import Headline2 from '../../../Components/layout/Headline2.vue';
import Button from '../../../Components/interactive/Button.vue';
import { PlusIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/solid';
import { useCodebooks } from '../../coding/codebooks/useCodebooks';
import CreateDialog from '../../../dialogs/CreateDialog.vue';
import ConfirmDialog from '../../../dialogs/ConfirmDialog.vue';
import { useCodebookPreview } from './useCodebookPreview';
import { router } from '@inertiajs/vue3';
import { useCodebookUpdate } from '../../../domain/codebooks/useCodebookUpdate';
import { useCodebookCreate } from '../../../domain/codebooks/useCodebookCreate';
import {useDeleteDialog} from "../../../dialogs/useDeleteDialog";
import DeleteDialog from "../../../dialogs/DeleteDialog.vue";
import ContrastText from "../../../Components/text/ContrastText.vue";

const { codebook: previewCodebook, close: closeCodebookPreview } =
  useCodebookPreview();
const {
  importCodebookSchema,
  createCodebook,
  importCodebook,
  deleteCodebook,
  updateCodebook,
  initCodebooks,
  codebooks,
} = useCodebooks();
const {
    message,
    challenge,
    target
} = useDeleteDialog()
const {
  close: closeUpdate,
  schema: updateCodebookSchema,
  codebook: codebookToUpdate,
} = useCodebookUpdate();
const {
  open: openCreateForm,
  close: closeCreateForm,
  schema: createCodebookSchema,
  codebook: codebookToImport,
} = useCodebookCreate();

const importSchema = ref(null);

initCodebooks();

// File handling for importing XML
const userCodebooks = inject('userCodebooks');
const publicCodebooks = inject('publicCodebooks');
const searchQueryPublicCodebooks = ref('');

const filteredPublicCodebooks = computed(() => {
  return publicCodebooks.filter((codebook) =>
    codebook.name
      .toLowerCase()
      .includes(searchQueryPublicCodebooks.value.toLowerCase())
  );
});

const reload = () => {
  router.get(window.location.href);
};
const deleteCodebookFromArray = (codebook) => {
  const index = codebooks.value.findIndex((cb) => cb.id === codebook.id);
  if (index !== -1) {
    codebooks.value.splice(index, 1);
  }
};
</script>

<template>
  <div>
    <div class="flex items-center justify-between">
      <Headline2>Codebooks of current Project</Headline2>
      <span class="space-x-1">
        <Button variant="outline-secondary" @click="openCreateForm()">
          <PlusIcon class="w-4 h-4" />
          <span>Create</span>
        </Button>
        <Button
          variant="outline-secondary"
          @click="importSchema = importCodebookSchema()"
        >
          <ArrowUpTrayIcon class="w-4 h-4" />
          <span>Import</span>
        </Button>
      </span>
    </div>
    <ul
      v-if="codebooks.length > 0"
      role="list"
      class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 xl:grid-cols-3"
    >
      <li
        v-for="codebook in codebooks"
        :key="codebook.name"
        class="col-span-1 flex flex-col relative h-full"
      >
        <CodebookItem
          class="h-full"
          :codebook="codebook"
          @delete="deleteCodebookFromArray(codebook)"
        />
      </li>
    </ul>
    <div v-else>
      <p class="text-sm text-gray-500">You didn't create any codebook</p>
    </div>
  </div>

  <div>
    <Headline2>My created Codebooks in other Projects</Headline2>
    <ul
      v-if="userCodebooks.length > 0"
      role="list"
      class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 xl:grid-cols-3"
    >
      <li
        v-for="codebook in userCodebooks"
        :key="codebook.name"
        class="col-span-1 flex flex-col relative"
      >
        <CodebookItem
          class="h-full"
          :codebook="codebook"
          :public="true"
          @importCodebook="importCodebook(codebook)"
        ></CodebookItem>
      </li>
    </ul>
    <div v-else>
      <p class="text-sm text-gray-500">
        No codebooks from other projects available
      </p>
    </div>
  </div>

  <div class="pb-24">
    <Headline2>Public Codebooks</Headline2>
    <input
      v-if="publicCodebooks.length > 0"
      v-model="searchQueryPublicCodebooks"
      type="search"
      placeholder="Search public codebooks..."
      class="mt-2 mb-3 w-1/2 rounded-md border-border shadow-sm"
    />
    <ul
      v-if="filteredPublicCodebooks.length > 0"
      role="list"
      class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6"
    >
      <li
        v-for="codebook in filteredPublicCodebooks"
        :key="codebook.name"
        class="col-span-1 flex flex-col relative"
      >
        <CodebookItem
          class="h-full"
          :codebook="codebook"
          :public="true"
          @importCodebook="importCodebook(codebook)"
        ></CodebookItem>
      </li>
    </ul>
    <div v-else>
      <p class="text-sm text-gray-500">No public codebooks available</p>
    </div>
  </div>

  <CreateDialog
    :title="
      codebookToImport
        ? `Import \'${codebookToImport.name}\' into this project`
        : 'Create a new Codebook'
    "
    :schema="createCodebookSchema"
    :submit="createCodebook"
    @cancelled="closeCreateForm"
  >
    <template #info>
      <div class="w-full block italic text-foreground/60 text-sm">
        When you set a codebook "public", anyone can import it on their project.
        When you set a codebook "shared with your teams", only members of your
        teams can import it on their projects.
      </div>
    </template>
  </CreateDialog>
  <CreateDialog
    :title="`Update ${codebookToUpdate?.name}`"
    :schema="updateCodebookSchema"
    :submit="updateCodebook"
    @cancelled="closeUpdate()"
  >
  </CreateDialog>
  <CreateDialog
    title="Import Codebook"
    :schema="importSchema"
    :submit="importCodebook"
    @created="reload"
    button-title="Upload now"
    @cancelled="importSchema = null"
  >
    <template #info>
      <div class="w-full block italic text-foreground/60 text-sm my-4">
        <p>
          This import is intended for codebook exports that support the
          <a
            href="https://www.qdasoftware.org/refi-qda-codebook"
            title="REFI website"
            target="_blank"
            rel="noopener noreferrer nofollow"
            class="font-semibold hover:underline"
            >REFI QDA Exchange Sandard</a
          >.
        </p>
        <p>
          We aim to support MaxQDA, NViVo, atlas.ti, f4analyse. If you have any
          problem, please
          <a
            href="mailto:openqda@uni-bremen.de"
            class="font-semibold hover:underline"
            >contact us</a
          >. You can also import codebooks from other projects, but they might
          not be fully functional.
        </p>
      </div>
    </template>
  </CreateDialog>
    <DeleteDialog
        :target="target"
        :message="message"
        :challenge="challenge"
        :submit="deleteCodebook"
        />
  <ConfirmDialog
    :title="`Preview of ${previewCodebook?.name}`"
    :show="!!previewCodebook"
    :showConfirm="false"
    cancelButtonLabel="Close"
    :static="false"
    @confirmed="closeCodebookPreview()"
    @cancelled="closeCodebookPreview()"
  >
    <template #info v-if="previewCodebook">
      <p class="py-4">
        {{ previewCodebook.name }} has
        {{ previewCodebook.codes?.length ?? 0 }} codes
      </p>
      <ul>
        <li
          v-for="code in previewCodebook.codes"
          :key="code.id"
          class="flex items-center my-2"
        >
          <div
            class="rounded-md w-full p-3 text-sm font-medium"
            :style="'background-color: ' + code.color"
          >
            <ContrastText>{{ code.name }}</ContrastText>
          </div>
        </li>
      </ul>
    </template>
  </ConfirmDialog>
</template>

<style scoped></style>
