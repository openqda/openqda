<script setup lang="ts">
import { computed, inject, ref } from 'vue';
import axios from 'axios';
import { debounce } from 'lodash';
import CodebookItem from './CodebookItem.vue';
import Headline2 from '../../../Components/layout/Headline2.vue';
import Button from '../../../Components/interactive/Button.vue';
import {
  PlusIcon,
  ArrowUpTrayIcon,
  ChevronRightIcon,
} from '@heroicons/vue/24/solid';
import { Collapse } from 'vue-collapsed';
import FormDialog from '../../../dialogs/FormDialog.vue';
import ConfirmDialog from '../../../dialogs/ConfirmDialog.vue';
import DeleteDialog from '../../../dialogs/DeleteDialog.vue';
import ContrastText from '../../../Components/text/ContrastText.vue';
import { useCodebooks } from '../../../domain/codebooks/useCodebooks';
import { useCodebookPreview } from './useCodebookPreview';
import { useCodebookUpdate } from '../../../domain/codebooks/useCodebookUpdate';
import { useCodebookCreate } from '../../../domain/codebooks/useCodebookCreate';
import { useDeleteDialog } from '../../../dialogs/useDeleteDialog';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { cn } from '../../../utils/css/cn';

const {
  codebook: previewCodebook,
  loading: previewLoading,
  close: closeCodebookPreview,
} = useCodebookPreview();
const {
  importCodebookSchema,
  createCodebook,
  importCodebook,
  deleteCodebook,
  updateCodebook,
  initCodebooks,
  codebooks,
} = useCodebooks();
const { message, challenge, target } = useDeleteDialog();
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
const showPrivate = ref(true);
const showOthers = ref(false);
const showPublic = ref(false);

initCodebooks();

// File handling for importing XML
const userCodebooks = inject('userCodebooks');

// Public Codebooks - Lazy Loading State
const publicCodebooksLoaded = ref(false);
const loading = ref(false);
const searchQuery = ref('');
const searching = ref(false);

// Pagination state
const currentPage = ref(1);
const pageSize = ref(10);
const totalPages = ref(0);
const totalCount = ref(0);

// Data state
const publicCodebooks = ref([]);
const searchResults = ref([]);

// ui state
const showCodeInfo = ref(false);

// Computed
const displayedCodebooks = computed(() => {
  return searchQuery.value ? searchResults.value : publicCodebooks.value;
});

const reload = () => {
  window.location.reload();
};

const submitUpdate = async (data) => {
  const result = await updateCodebook(data);
  updateCodebookSchema.value = null;
  return result;
};
const cancelUpdate = () => {
  closeUpdate();
  updateCodebookSchema.value = null;
};
const cancelImport = () => {
  codebookToImport.value = null;
};

const deleteCodebookFromArray = (codebook) => {
  const index = codebooks.value.findIndex((cb) => cb.id === codebook.id);
  if (index !== -1) {
    codebooks.value.splice(index, 1);
  }
};

const handleWithReload = async (fn, ...args) => {
  const result = await attemptAsync(() => fn(...args));
  setTimeout(() => window.location.reload(), 1000);
  return result;
};

// Public Codebooks Methods
const loadPublicCodebooks = async (page = 1) => {
  if (
    showPublic.value &&
    publicCodebooksLoaded.value &&
    page === currentPage.value
  ) {
    return; // Already showing this page
  }

  showPublic.value = true;
  loading.value = true;

  try {
    const response = await axios.get('/api/codebooks/public', {
      params: {
        page,
        per_page: pageSize.value,
      },
    });

    publicCodebooks.value = response.data.data;
    currentPage.value = response.data.current_page;
    totalPages.value = response.data.last_page;
    totalCount.value = response.data.total;
    publicCodebooksLoaded.value = true;
  } catch (error) {
    console.error('Failed to load public codebooks:', error);
  } finally {
    loading.value = false;
  }
};

const changePageSize = async () => {
  // When page size changes, we need to reload data
  // If total items would fit in fewer pages with new size, adjust current page
  const maxPageWithNewSize = Math.ceil(totalCount.value / pageSize.value);

  // Ensure we're not on a page that doesn't exist with the new page size
  if (currentPage.value > maxPageWithNewSize && maxPageWithNewSize > 0) {
    currentPage.value = maxPageWithNewSize;
  } else if (currentPage.value === 0 && totalCount.value > 0) {
    currentPage.value = 1;
  }

  // Force reload by temporarily clearing the current page
  const targetPage = currentPage.value || 1;
  currentPage.value = 0; // This ensures loadPublicCodebooks won't skip the load

  await loadPublicCodebooks(targetPage);
};

const nextPage = async () => {
  if (currentPage.value < totalPages.value) {
    await loadPublicCodebooks(currentPage.value + 1);
  }
};

const previousPage = async () => {
  if (currentPage.value > 1) {
    await loadPublicCodebooks(currentPage.value - 1);
  }
};

const performSearch = async () => {
  if (searchQuery.value.length < 3) {
    searchResults.value = [];
    return;
  }

  searching.value = true;
  try {
    const response = await axios.get('/api/codebooks/search', {
      params: {
        q: searchQuery.value,
        limit: 20,
      },
    });
    searchResults.value = response.data.data;
  } catch (error) {
    console.error('Search failed:', error);
  } finally {
    searching.value = false;
  }
};

const clearSearch = () => {
  searchQuery.value = '';
  searchResults.value = [];
};

const debounceSearch = debounce(performSearch, 300);
</script>

<template>
  <div>
    <div class="block md:flex items-center justify-between">
      <button
        @click="showPrivate = !showPrivate"
        class="flex items-center my-6 md:my-0"
      >
        <ChevronRightIcon
          :class="
            cn(
              'w-4 h-4 transition-all duration-300 transform me-2',
              showPrivate && 'rotate-90'
            )
          "
        />
        <Headline2>Codebooks of current Project</Headline2>
      </button>
      <span class="block md:flex md:gap-1">
        <FormDialog
          title="Create a new Codebook"
          :schema="createCodebookSchema"
          class="w-100 md:w-auto"
          :submit="(data) => handleWithReload(createCodebook, data)"
          @cancelled="closeCreateForm"
        >
          <template #trigger="createCodebookTrigger">
            <Button
              variant="outline-secondary"
              class="w-100 md:w-auto"
              @click="createCodebookTrigger.onClick(openCreateForm)"
            >
              <PlusIcon class="w-4 h-4" />
              <span>Create</span>
            </Button>
          </template>
          <template #info>
            <div class="w-full block italic text-foreground/60 text-sm">
              When you set a codebook "public", anyone can import it on their
              project. On private, only members of your teams can import it into
              their projects.
            </div>
          </template>
        </FormDialog>

        <FormDialog
          title="Import Codebook"
          :schema="importSchema"
          :submit="importCodebook"
          class="w-100 md:w-auto"
          @created="reload"
          button-title="Upload now"
          @cancelled="importSchema = null"
        >
          <template #trigger="importDialogTrigger">
            <Button
              variant="outline-secondary"
              class="w-100 md:w-auto"
              @click="
                importDialogTrigger.onClick(
                  () => (importSchema = importCodebookSchema())
                )
              "
            >
              <ArrowUpTrayIcon class="w-4 h-4" />
              <span>Import</span>
            </Button>
          </template>
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
                We aim to support all vendors that implement REFI. If you have
                any problem, please
                <a
                  href="mailto:openqda@uni-bremen.de"
                  class="font-semibold hover:underline"
                  >contact us</a
                >. You can also import codebooks from other projects, but they
                might not be fully functional.
              </p>
            </div>
          </template>
        </FormDialog>
      </span>
    </div>
    <Collapse :when="showPrivate" v-if="codebooks.length > 0">
      <ul
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
            :show-email="true"
            @delete="deleteCodebookFromArray(codebook)"
          />
        </li>
      </ul>
    </Collapse>
    <div v-else>
      <p class="text-sm text-gray-500">You didn't create any codebook</p>
    </div>
  </div>

  <div>
    <button
      @click="showOthers = !showOthers"
      class="flex items-center my-6 md:my-0"
    >
      <ChevronRightIcon
        :class="
          cn(
            'w-4 h-4 transition-all duration-300 transform me-2',
            showOthers && 'rotate-90'
          )
        "
      />
      <Headline2 class="text-left md:text-center"
        >My created Codebooks in other Projects</Headline2
      >
    </button>
    <Collapse :when="showOthers" v-if="userCodebooks.length > 0">
      <ul
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
            :show-email="true"
            @importCodebook="
              (codebook) => handleWithReload(importCodebook, codebook)
            "
          ></CodebookItem>
        </li>
      </ul>
    </Collapse>
    <div v-else>
      <p class="text-sm text-gray-500">
        No codebooks from other projects available
      </p>
    </div>
  </div>

  <!-- Public Codebooks Section with Lazy Loading -->
  <div class="pb-24">
    <button
      @click="loadPublicCodebooks()"
      class="flex items-center my-6 md:my-0"
    >
      <ChevronRightIcon
        :class="
          cn(
            'w-4 h-4 transition-all duration-300 transform me-2',
            showPublic && 'rotate-90'
          )
        "
      />
      <Headline2>Public Codebooks</Headline2>
      <span v-if="!publicCodebooksLoaded" class="ml-2 text-sm text-gray-500"
        >●</span
      >
    </button>

    <Collapse :when="showPublic">
      <div class="mt-3">
        <!-- Controls Bar -->
        <div class="flex flex-col sm:flex-row gap-4 mb-4">
          <!-- Page Size Selector -->
          <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700">Show:</label>
            <select
              v-model="pageSize"
              @change="changePageSize"
              class="rounded-md border-gray-300 text-sm"
            >
              <option value="10">10</option>
              <option value="15">15</option>
              <option value="20">20</option>
            </select>
            <span class="text-sm text-gray-700">per page</span>
          </div>

          <!-- Search Box -->
          <div class="flex-1 max-w-md">
            <div class="relative">
              <input
                v-model="searchQuery"
                @input="debounceSearch"
                type="search"
                placeholder="Search by codebook name or user email..."
                class="w-full rounded-md border-gray-300 pr-8"
              />
              <button
                v-if="searchQuery"
                @click="clearSearch"
                class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                ×
              </button>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center py-8">
          <div
            class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"
          ></div>
          <span class="ml-2">Loading public codebooks...</span>
        </div>

        <!-- Codebooks Grid -->
        <div v-else-if="displayedCodebooks.length > 0">
          <ul
            role="list"
            class="grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 xl:grid-cols-3"
          >
            <li
              v-for="codebook in displayedCodebooks"
              :key="codebook.id"
              class="col-span-1 flex flex-col relative"
            >
              <CodebookItem
                class="h-full"
                :codebook="codebook"
                :public="true"
                :show-email="true"
                @importCodebook="importCodebook(codebook)"
              />
            </li>
          </ul>

          <!-- Pagination Controls (only show when not searching) -->
          <div
            v-if="!searchQuery && totalPages > 1"
            class="mt-6 flex items-center justify-center"
          >
            <nav class="flex items-center gap-2">
              <button
                @click="previousPage"
                :disabled="currentPage === 1"
                class="px-3 py-1 rounded-md bg-white border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Previous
              </button>

              <span class="px-3 py-1 text-sm text-gray-700">
                Page {{ currentPage }} of {{ totalPages }} ({{ totalCount }}
                total)
              </span>

              <button
                @click="nextPage"
                :disabled="currentPage === totalPages"
                class="px-3 py-1 rounded-md bg-white border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Next
              </button>
            </nav>
          </div>
        </div>

        <!-- No Results -->
        <div v-else-if="searchQuery && !loading" class="py-8 text-center">
          <p class="text-sm text-gray-500">
            No public codebooks found matching "{{ searchQuery }}"
          </p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading" class="py-8 text-center">
          <p class="text-sm text-gray-500">No public codebooks available</p>
        </div>
      </div>
    </Collapse>
  </div>
  <FormDialog
    :title="`Update ${codebookToUpdate?.name}`"
    button-title="Update"
    :schema="updateCodebookSchema"
    :submit="submitUpdate"
    :show="!!updateCodebookSchema"
    @cancelled="cancelUpdate()"
    @created="reload"
  />
  <FormDialog
    :title="`Import ${codebookToImport?.name}`"
    button-title="Update"
    :schema="createCodebookSchema"
    :submit="createCodebook"
    :show="!!codebookToImport"
    @cancelled="cancelImport()"
    @created="reload"
  />

  <DeleteDialog
    :target="target"
    :message="message"
    :challenge="challenge"
    :submit="deleteCodebook"
  />
  <ConfirmDialog
    :title="`Preview of ${previewCodebook?.name || 'Codebook'}`"
    :show="!!previewCodebook || previewLoading"
    :showConfirm="false"
    cancelButtonLabel="Close"
    :static="false"
    @confirmed="closeCodebookPreview()"
    @cancelled="closeCodebookPreview()"
  >
    <template #info>
      <div v-if="previewLoading" class="flex items-center justify-center py-8">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"
        ></div>
        <span class="ml-2">Loading codebook codes...</span>
      </div>
      <div v-else-if="previewCodebook">
        <p class="py-4">
          {{ previewCodebook.name }} has
          {{ previewCodebook.codes?.length ?? 0 }} codes
        </p>
        <Button
          variant="outline-secondary"
          size="sm"
          @click="showCodeInfo = !showCodeInfo"
        >
          <span v-if="showCodeInfo">Hide Details</span>
          <span v-else>Show Details</span>
        </Button>
        <ul v-if="previewCodebook.codes && previewCodebook.codes.length > 0">
          <li
            v-for="code in previewCodebook.codes"
            :key="code.id"
            class="flex items-start my-2"
          >
            <div
              v-if="showCodeInfo && code.depth"
              :style="'margin-left: ' + code.depth + 'rem;'"
            >
              └
            </div>
            <div
              class="rounded-md w-full p-3 text-sm font-medium"
              :style="`background-color: ${code.color};`"
            >
              <ContrastText class="block w-full">{{ code.name }}</ContrastText>
              <ContrastText
                v-if="code.description && showCodeInfo"
                class="block w-full text-xs"
              >
                <span class="grow">{{ code.description }}</span>
              </ContrastText>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-gray-500">This codebook has no codes.</p>
      </div>
    </template>
  </ConfirmDialog>
</template>

<style scoped></style>
