<template>
  <div class="flow-root w-full ml-auto mr-auto pb-16">
    <div class="pb-1.5 mt-2 rounded-md">
      <!-- Search Input with loading indicator -->
      <div class="flex items-center gap-x-1.5 mb-2 relative my-6 md:my-0">
        <TextInput
          v-model="filters.query"
          ref="searchInput"
          placeholder="Search..."
          type="text"
          name="searchQuery"
          id="searchQuery"
          @input="handleSearch"
          :disabled="isLoading"
        />
        <!-- Search loading indicator -->
        <div
          v-if="isSearching || isLoading"
          class="absolute right-3 top-1/2 transform -translate-y-1/2"
        >
          <svg
            class="animate-spin h-4 w-4 text-foreground/50"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            />
          </svg>
        </div>
      </div>

      <!-- Date Filters -->
      <div
        class="block md:flex justify-start items-end gap-4 mb-4 my-6 md:my-4"
      >
        <div>
          <label class="block text-sm font-medium text-foreground/80"
            >Before</label
          >
          <TextInput
            type="date"
            v-model="filters.before_date"
            @change="handleDateChange"
            :disabled="isLoading"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-foreground/80"
            >After</label
          >
          <TextInput
            type="date"
            v-model="filters.after_date"
            @change="handleDateChange"
            :disabled="isLoading"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-foreground/80"
            >Between</label
          >
          <div class="flex gap-2">
            <TextInput
              type="date"
              v-model="filters.start_date"
              @change="handleDateChange"
              :disabled="isLoading"
            />
            <TextInput
              type="date"
              v-model="filters.end_date"
              @change="handleDateChange"
              :disabled="isLoading"
            />
          </div>
        </div>
        <Button class="ms-auto" @click="exportAudit">Export Audit</Button>
      </div>

      <!-- Model Type Filters -->
      <div class="filters block md:flex gap-4 w-full justify-start my-4">
        <label
          v-for="type in modelTypes"
          :key="type"
          class="flex items-center gap-1 my-4 md:my-0"
          :class="{ 'opacity-50': isLoading }"
        >
          <Checkbox
            :value="type"
            :checked="filters.models.includes(type)"
            @change="toggleModelFilter(type)"
            :disabled="isLoading"
            class="mr-2"
          />

          {{ type }}
          <span class="text-xs text-foreground/50">
            ({{ getModelCount(type) }})
          </span>
        </label>
      </div>
    </div>
    <!-- Audit List with loading overlay -->
    <div class="relative">
      <!-- Loading overlay -->
      <div
        v-if="isLoading"
        class="absolute inset-0 bg-surface/50 flex items-center justify-center z-10"
      >
        <svg
          class="animate-spin h-8 w-8 text-primary"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          />
        </svg>
      </div>

      <ul role="list" class="-mb-8 mt-4">
        <li v-for="(audit, auditIdx) in audits.data" :key="audit.id">
          <div class="relative pb-8">
            <span
              v-if="auditIdx !== audits.data.length - 1"
              class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-background"
              aria-hidden="true"
            ></span>
            <div class="relative flex items-start gap-3">
              <div>
                <div class="relative px-1">
                  <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 ring-8 ring-white"
                  >
                    <div
                      v-if="audit.user_profile_picture"
                      class="flex text-sm relative overflow-hidden transition w-8 h-8 border-2 border-transparent focus:outline-hidden focus:border-gray-300"
                    >
                      <ProfileImage
                        :src="audit.user_profile_picture"
                        alt="profile image"
                      />
                    </div>
                    <UserCircleIcon
                      v-else
                      class="h-5 w-5 text-foreground/80"
                      aria-hidden="true"
                    />
                  </div>
                </div>
              </div>
              <div class="min-w-0 flex-1 py-1">
                <div class="text-sm text-foreground/60">
                  <span class="font-semibold text-foreground">
                    {{ audit.user_id }}
                  </span>
                  performed at {{ audit.created_at }}
                </div>
                <div class="mt-2 text-sm text-foreground/80">
                  <div class="block w-full font-semibold">
                    {{ audit.model }}: {{ audit.event }}
                  </div>
                  <ul>
                    <template v-if="audit.event !== 'deleted'">
                      <li v-for="(value, key) in audit.new_values" :key="key">
                        <template v-if="audit.event === 'created'">
                          <span
                            v-if="
                              audit.model === 'Codebook' &&
                              key === 'description' &&
                              value === null
                            "
                          ></span>
                          <span v-else
                            >Created
                            <span class="font-semibold text-foreground">{{
                              audit.model
                            }}</span>
                            with
                            <span class="font-semibold">{{ key }}</span>
                            set to
                          </span>
                          <span
                            class="px-1 rounded font-semibold font-mono"
                            v-if="audit.model === 'Code' && key === 'color'"
                            :style="'background-color:' + value"
                            >{{ value }}</span
                          >
                          <span
                            v-else-if="audit.model === 'Selection'"
                            class="font-semibold font-mono"
                          >
                            {{
                              value.length > 50
                                ? value.substring(0, 50) + '...'
                                : value
                            }}
                          </span>
                          <span
                            v-else-if="
                              audit.model === 'Codebook' && value !== ''
                            "
                          >
                            <span v-if="key === 'properties'">
                              public: {{ JSON.parse(value).sharedWithPublic }}
                            </span>
                            <span v-else class="font-semibold font-mono">
                              {{ value }}
                            </span>
                          </span>
                          <span v-else class="font-semibold font-mono">{{
                            value
                          }}</span>
                        </template>
                        <template v-else-if="audit.event === 'updated'">
                          <span v-if="key === 'team_id'">Created team</span>
                          <span
                            v-else-if="
                              audit.model === 'Codebook' &&
                              key === 'description' &&
                              value === null
                            "
                          ></span>
                          <span v-else
                            >Modified {{ audit.model }} {{ key }} to
                            <span
                              v-if="audit.model === 'Code' && key === 'color'"
                              class="px-1 rounded font-semibold font-mono"
                              :style="'background-color:' + value"
                              >{{ value }}</span
                            >
                            <span v-else-if="audit.model === 'Codebook'">
                              <span v-if="isJSON(value)">
                                public: {{ JSON.parse(value).sharedWithPublic }}
                              </span>
                              <span v-else>{{ value }}</span>
                            </span>
                            <span v-else>{{ value }}</span>
                          </span>
                        </template>
                        <template v-else-if="audit.event === 'content updated'">
                          {{ value }}
                        </template>
                        <template v-else-if="audit.event === 'source.locked'">
                          <span
                            >Locked {{ audit.model }}
                            <span class="font-semibold">{{
                              value.replace(' has been locked', '')
                            }}</span></span
                          >
                        </template>
                        <template v-else>
                          {{ audit.event }} on {{ audit.model }} for key
                          {{ key }} with value {{ value }}
                        </template>
                      </li>
                    </template>
                    <template v-else-if="audit.event === 'deleted'">
                      <li>
                        <span class="font-semibold">{{ audit.model }}</span>
                        <span
                          >{{ audit.name ?? audit.old_values.name }} was
                          deleted</span
                        >
                      </li>
                    </template>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </li>
      </ul>

      <!-- Pagination -->
      <div v-if="audits.last_page > 1" class="mt-4 flex justify-center">
        <nav
          class="flex items-center justify-between"
          :class="{ 'opacity-50': isLoading }"
        >
          <Button
            :disabled="audits.current_page === 1 || isLoading"
            @click="changePage(audits.current_page - 1)"
          >
            Previous
          </Button>
          <span class="mx-4">
            Page {{ audits.current_page }} of {{ audits.last_page }}
          </span>
          <Button
            :disabled="audits.current_page === audits.last_page || isLoading"
            @click="changePage(audits.current_page + 1)"
          >
            Next
          </Button>
        </nav>
      </div>
    </div>
  </div>
</template>
<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import { debounce } from '../../utils/dom/debounce.js';
import { useAudit } from '../../domain/audit/useAudit.js';
import { useExport } from '../../exchange/useExport.js';
import Checkbox from '../Checkbox.vue';
import Button from '../interactive/Button.vue';
import { flashMessage } from '../notification/flashMessage.js';
import ProfileImage from '../user/ProfileImage.vue';
import TextInput from '../../form/TextInput.vue';

const searchInput = ref(null);
const props = defineProps({
  projectId: {
    type: String,
    default: null,
  },
  context: String,
});

const { audits, auditCounts, loadAudits, loadAllAudits, forProjectId } =
  useAudit();
const { exportAuditToCSV } = useExport();

// Constants
const modelTypes = [
  'Source',
  'Selection',
  'Code',
  'Project',
  'Codebook',
  'Note',
  'Variable',
];
const PER_PAGE = 20;

// State
const filters = ref({
  query: '',
  models: modelTypes, // All selected by default
  before_date: null,
  after_date: null,
  start_date: null,
  end_date: null,
  per_page: PER_PAGE,
  project_id: props.projectId,
});
const isLoading = ref(false);
const isSearching = ref(false);
const error = ref(null);
let searchTimeout = null;

const getModelCount = (type) => {
  return auditCounts.value[type] || 0;
};

const exportAudit = async () => {
  try {
    isLoading.value = true;
    const {
      success,
      audits: allAudits,
      error: exportError,
    } = await loadAllAudits({ projectId: props.projectId });
    if (!success || exportError) {
      throw exportError ?? new Error('Failed to load audits for export');
    }
    await exportAuditToCSV({ audits: allAudits });
  } catch (err) {
    console.error('Error exporting audits:', err);
    flashMessage(err.message, { type: 'error' });
  } finally {
    isLoading.value = false;
  }
};

// Utility functions
function isJSON(str) {
  try {
    JSON.parse(str);
    return true;
  } catch {
    return false;
  }
}

// Methods
const toggleModelFilter = (type) => {
  if (filters.value.models.includes(type)) {
    // Remove the type from the models array if it's already there
    filters.value.models = filters.value.models.filter(
      (model) => model !== type
    );
  } else {
    // Add the type to the models array if it's not there
    filters.value.models.push(type);
  }
  fetchAudits(1);
};

const handleDateChange = () => {
  fetchAudits(1); // Reset to page 1 on date change
};

const fetchAudits = debounce(async (page = 1) => {
  const wasSearchFocused = document.activeElement === searchInput.value;
  try {
    isLoading.value = true;
    error.value = null;
    const {
      success,
      response,
      error: loadError,
    } = await loadAudits({
      page,
      projectId: props.projectId,
      filters: filters.value,
    });
    if (!success || loadError) {
      throw new Error(response.data.message || 'Failed to fetch audits');
    }
  } catch (err) {
    console.error('Error fetching audits:', err);
    error.value = err.message || 'An error occurred while fetching audit data';
    flashMessage(err.value, { type: 'error' });
  } finally {
    isLoading.value = false;
    isSearching.value = false;

    // After successful fetch, restore focus if it was on search
    if (wasSearchFocused) {
      nextTick(() => {
        searchInput.value?.focus();
      });
    }
  }
}, 1000);

const handleSearch = debounce(async () => {
  isSearching.value = true;
  await fetchAudits(1);
  isSearching.value = false;
}, 100);

const changePage = (page) => {
  if (!isLoading.value) {
    fetchAudits(page);
  }
};

onMounted(() => {
  // we load new audits on mount when we have switched
  // the project
  if (forProjectId.value !== props.projectId) {
    fetchAudits(1);
  }
});

// Watch for prop changes
watch(
  () => props.projectId,
  () => {
    filters.value.project_id = props.projectId;
    fetchAudits(1);
  }
);

// Cleanup
onUnmounted(() => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }
});
</script>

<style scoped></style>
