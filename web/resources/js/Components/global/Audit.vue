<template>
  <div
    :class="
      context === 'homePage'
        ? 'flow-root w-full ml-auto mr-auto'
        : 'flow-root md:w-3/4 lg:w-1/2 ml-auto mr-auto'
    "
  >
    <div class="pb-1.5 mt-2 rounded-md">
      <!-- Search Input with loading indicator -->
      <div class="flex items-center gap-x-1.5 mb-2 relative">
        <input
          v-model="filters.query"
          ref="searchInput"
          placeholder="Search..."
          type="text"
          name="searchQuery"
          id="searchQuery"
          @input="handleSearch"
          :disabled="isLoading"
          class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
        />
        <!-- Search loading indicator -->
        <div
          v-if="isSearching || isLoading"
          class="absolute right-3 top-1/2 transform -translate-y-1/2"
        >
          <svg
            class="animate-spin h-4 w-4 text-gray-500"
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
      <div class="flex justify-center space-x-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Before</label>
          <input
            type="date"
            v-model="filters.before_date"
            @change="handleDateChange"
            :disabled="isLoading"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">After</label>
          <input
            type="date"
            v-model="filters.after_date"
            @change="handleDateChange"
            :disabled="isLoading"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Between</label>
          <div class="flex space-x-2">
            <input
              type="date"
              v-model="filters.start_date"
              @change="handleDateChange"
              :disabled="isLoading"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100"
            />
            <input
              type="date"
              v-model="filters.end_date"
              @change="handleDateChange"
              :disabled="isLoading"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm disabled:bg-gray-100"
            />
          </div>
        </div>
      </div>

      <!-- Model Type Filters -->
      <div class="filters flex space-x-4 w-full justify-center mb-4">
        <label
          v-for="type in modelTypes"
          :key="type"
          class="flex items-center"
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
          <span class="text-xs text-gray-500">
            ({{ getModelCount(type) }})
          </span>
        </label>
      </div>
    </div>
    <!-- Audit List with loading overlay -->
    <div class="relative">
      <!-- Loading overlay -->
      <div
        v-if="isLoading && audits.data.length"
        class="absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center z-10"
      >
        <svg
          class="animate-spin h-8 w-8 text-cerulean-600"
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
              class="absolute left-5 top-5 -ml-px h-full w-0.5 bg-gray-200"
              aria-hidden="true"
            ></span>
            <div class="relative flex items-start space-x-3">
              <div>
                <div class="relative px-1">
                  <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 ring-8 ring-white"
                  >
                    <div
                      v-if="audit.user_profile_picture"
                      class="flex text-sm relative overflow-hidden transition w-8 h-8 border-2 border-transparent focus:outline-none focus:border-gray-300"
                    >
                      <img
                        class="object-cover w-full h-auto rounded-full"
                        :src="audit.user_profile_picture"
                        :alt="audit.user_id"
                      />
                    </div>
                    <UserCircleIcon
                      v-else
                      class="h-5 w-5 text-gray-500"
                      aria-hidden="true"
                    />
                  </div>
                </div>
              </div>
              <div class="min-w-0 flex-1 py-1">
                <div class="text-sm text-gray-500">
                  <span class="font-medium text-gray-900">
                    {{ audit.user_id }}
                  </span>
                  performed at {{ audit.created_at }}
                </div>
                <div class="mt-2 text-sm text-gray-700">
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
                            <span class="font-semibold text-porsche-400">{{
                              audit.model
                            }}</span>
                            with
                            <span class="font-semibold">{{ key }}</span>
                            set to
                          </span>
                          <span
                            class="px-1 rounded font-semibold font-mono"
                            v-if="audit.model === 'Code'"
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
                              v-if="audit.model === 'Code'"
                              :style="'background-color:' + value"
                              >{{ value }}</span
                            >
                            <span v-if="audit.model === 'Codebook'">
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
                        <template v-else>
                          {{ audit.event }} on {{ audit.model }} for key
                          {{ key }} with value {{ value }}
                        </template>
                      </li>
                    </template>
                    <template v-else-if="audit.event === 'deleted'">
                      <li>{{ audit.old_values.name }} was deleted</li>
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
          <button
            :disabled="audits.current_page === 1 || isLoading"
            @click="changePage(audits.current_page - 1)"
            class="px-3 py-1 rounded-md bg-white border"
            :class="{
              'opacity-50 cursor-not-allowed':
                audits.current_page === 1 || isLoading,
            }"
          >
            Previous
          </button>
          <span class="mx-4">
            Page {{ audits.current_page }} of {{ audits.last_page }}
          </span>
          <button
            :disabled="audits.current_page === audits.last_page || isLoading"
            @click="changePage(audits.current_page + 1)"
            class="px-3 py-1 rounded-md bg-white border"
            :class="{
              'opacity-50 cursor-not-allowed':
                audits.current_page === audits.last_page || isLoading,
            }"
          >
            Next
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted, watch, onUnmounted, nextTick } from 'vue';
import {
  ChevronDownIcon,
  UserCircleIcon,
  XCircleIcon,
} from '@heroicons/vue/20/solid';
import Button from '../interactive/Button.vue';
import Checkbox from '../Checkbox.vue';
const searchInput = ref(null);
const props = defineProps({
  audits: Object,
  projectId: {
    type: String,
    default: null,
  },
  context: String,
});

// Constants
const modelTypes = ['Source', 'Selection', 'Code', 'Project', 'Codebook'];
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

// Initialize audits with empty array
const audits = ref({
  data: [],
  current_page: 1,
  last_page: 1,
});

const getModelCount = (type) => {
  if (!Array.isArray(audits.value.data)) {
    return 0;
  }
  return audits.value.data.filter((audit) => audit.model === type).length;
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

// Vanilla JS debounce
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
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

const fetchAudits = async (page = 1) => {
  console.log(page);
  try {
    isLoading.value = true;
    error.value = null;
    const wasSearchFocused = document.activeElement === searchInput.value;
    const params = new URLSearchParams();

    Object.entries(filters.value).forEach(([key, value]) => {
      if (value !== null && value !== '') {
        if (Array.isArray(value)) {
          value.forEach((v) => params.append(`${key}[]`, v));
        } else {
          params.append(key, value);
        }
      }
    });

    params.append('page', page);

    const endpoint =
      props.context === 'projectPage'
        ? `/audits/${props.projectId}`
        : '/audits';

    const response = await axios.get(`${endpoint}?${params.toString()}`);
    if (response.data.success) {
      audits.value = {
        ...response.data.audits,
        data: Array.isArray(response.data.audits.data)
          ? response.data.audits.data
          : Object.values(response.data.audits.data || {}),
      };
    } else {
      throw new Error(response.data.message || 'Failed to fetch audits');
    }

    // After successful fetch, restore focus if it was on search
    if (wasSearchFocused) {
      nextTick(() => {
        searchInput.value?.focus();
      });
    }
  } catch (err) {
    console.error('Error fetching audits:', err);
    error.value = err.message || 'An error occurred while fetching audit data';
    audits.value = { data: [], current_page: 1, last_page: 1 };
  } finally {
    isLoading.value = false;
  }
};

const handleSearch = debounce(async () => {
  try {
    isSearching.value = true;
    await fetchAudits(1);
  } finally {
    isSearching.value = false;
  }
}, 300);

const handleModelFilter = () => {
  fetchAudits(1);
};

const changePage = (page) => {
  if (!isLoading.value) {
    fetchAudits(page);
  }
};

// Lifecycle
onMounted(() => {
  if (props.audits) {
    audits.value = {
      ...props.audits,
      data: Array.isArray(props.audits.data)
        ? props.audits.data
        : Object.values(props.audits.data || {}),
    };
  }
  // Fetch initial data if no props provided
  else {
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
