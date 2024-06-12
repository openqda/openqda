<template>
  <table class="w-full mt-4 border-collapse border-0 overflow-show">
    <thead>
      <tr class="border-b border-gray-200 align-middle" :class="props.rowClass">
        <th></th>
        <th
          scope="col"
          class="p-2 text-center text-xs font-medium uppercase text-gray-500 sm:pl-0"
        >
          <a
            href
            @click.prevent="sort('name')"
            class="flex tracking-wider"
            title="Sort by file name"
          >
            <span>File</span>
            <span>
              <ChevronUpIcon
                class="h-4 w-4 gray-500"
                v-if="sorter.key === 'name' && sorter.ascending === true"
              />
              <ChevronDownIcon
                class="h-4 w-4 gray-500"
                v-if="sorter.key === 'name' && sorter.ascending === false"
              />
            </span>
          </a>
        </th>

        <th
          scope="col"
          class="p-2 text-xs font-medium uppercase text-gray-500 sm:pl-0"
        >
          <a
            href
            @click.prevent="sort('type')"
            class="flex tracking-wider justify-center text-center"
            title="Sort by data type"
          >
            <span>Type</span>
            <span>
              <ChevronUpIcon
                class="h-4 w-4 gray-500"
                v-if="sorter.key === 'type' && sorter.ascending === true"
              />
              <ChevronDownIcon
                class="h-4 w-4 gray-500"
                v-if="sorter.key === 'type' && sorter.ascending === false"
              />
            </span>
          </a>
        </th>
        <th
          scope="col"
          class="p-2 text-xs font-medium uppercase tracking-wider text-gray-500 sm:pl-0"
        >
          <a
            href
            @click.prevent="sort('date')"
            class="flex justify-center"
            title="Sort by upload date"
          >
            <span>Date</span>
            <span>
              <ChevronUpIcon
                class="h-4 w-4 gray-500"
                v-if="sorter.key === 'date' && sorter.ascending === true"
              />
              <ChevronDownIcon
                class="h-4 w-4 gray-500"
                v-if="sorter.key === 'date' && sorter.ascending === false"
              />
            </span>
          </a>
        </th>
        <th
          scope="col"
          class="p-2 text-center text-xs font-medium uppercase tracking-wider text-gray-500 sm:pl-0"
        >
          <a
            href
            @click.prevent="sort('user')"
            class="flex justify-center"
            title="Sort by username"
          >
            <span>By</span>
            <span>
              <ChevronUpIcon
                class="h-4 w-4 gray-500"
                v-if="sorter.key === 'user' && sorter.ascending === true"
              />
              <ChevronDownIcon
                class="h-4 w-4 gray-500"
                v-if="sorter.key === 'user' && sorter.ascending === false"
              />
            </span>
          </a>
        </th>
        <th
          scope="col"
          v-show="$props.actions?.length"
          class="p-2 text-center text-xs font-medium uppercase text-gray-500 sm:pl-0"
        >
          <span class="tracking-wider">Actions</span>
        </th>
      </tr>
    </thead>
    <tbody>
      <tr
        v-for="(document, index) in docs"
        :key="document.id"
        class="text-sm border-b border-gray-200 text-ellipsis overflow-hidden justify-center items-center align-middle hover:bg-silver-50"
        :class="[
          document.selected ? 'bg-silver-50' : '',
          document.converted ? '' : '',
          props.rowClass,
        ]"
      >
        <td class="text-center pl-2">
          <LockClosedIcon
            v-if="document.variables && document.variables.isLocked"
            class="w-4 h-4 text-porsche-400"
          />
        </td>
        <td class="py-4 flex items-center w-auto gap-2">
          <a
            @click="emit('select', document)"
            :class="
              document.converted
                ? 'hover:text-porsche-400 cursor-pointer'
                : 'text-silver-300 pointer-events-none'
            "
            class="tracking-wider"
          >
            {{ document.name }}
          </a>
          <div
            v-if="!document.converted && !document.isConverting"
            class="flex items-center bg-yellow-100 text-yellow-800 font-semibold px-2 py-1 mx-2 text-xs"
          >
            <ExclamationTriangleIcon
              class="w-3 h-3 text-yellow-800 mr-1"
            ></ExclamationTriangleIcon>
            <span class="hidden md:block whitespace-nowrap"
              >not ready to be coded - retry elaboration</span
            >
          </div>

          <div
            v-if="document.isConverting"
            class="flex items-center bg-porsche-400 text-white text-xs font-semibold px-2 py-1 rounded-full"
          >
            <div class="animate-spin mr-1">
              <!-- Replace with your rotating arrow icon -->
              <ArrowPathIcon class="text-white w-3 h-3"></ArrowPathIcon>
            </div>
            Converting
          </div>
          <div
            v-if="document.failed"
            class="flex items-center bg-red-700 text-white text-xs font-semibold px-2 py-1 rounded-full"
          >
            <XMarkIcon class="text-white w-3 h-3"></XMarkIcon>
            Failed
          </div>
        </td>

        <td class="py-2">
          <!-- TODO make this open-close impl -->
          <div :title="dataTypeTitle(document.type)" class="w-full text-center">
            <DocumentTextIcon
              v-if="document.type === 'text'"
              class="h-4 w-4 gray-500 ml-auto mr-auto"
            />
            <SpeakerWaveIcon
              v-if="document.type === 'audio'"
              class="h-4 w-4 gray-500 ml-auto mr-auto"
            />
          </div>
        </td>
        <td class="py-2 text-center tracking-wider">
          {{ document.date }}
        </td>
        <td class="py-2 text-center tracking-wider w-8 h-8">
          <img
            v-if="document.userPicture"
            class="object-cover w-full h-8 rounded-full"
            :src="document.userPicture"
            :title="document.user"
            :alt="document.user"
          />
          <div
            v-else
            class="flex items-center justify-center w-full h-full rounded-full bg-gray-200"
          >
            <span class="text-gray-500">{{ document.user }}</span>
          </div>
        </td>
        <td
          v-show="$props.actions?.length"
          class="py-2 text-center tracking-wider justify-center align-middle items-center relative"
        >
          <button
            @click="toggleMenu(document.id)"
            class="hover:text-porsche-400 focus:text-porsche-400 cursor-pointer menu-toggle"
          >
            <EllipsisVerticalIcon
              class="w-4 h-4 menu-toggle z-0"
            ></EllipsisVerticalIcon>
          </button>

          <div
            v-show="isMenuOpen(document.id)"
            v-click-outside="{ callback: handleOutsideClick }"
            class="absolute right-0 mt-2 py-2 w-60 center bg-white rounded-md border border-silver-300 shadow-xl z-20"
          >
            <span
              v-for="action in $props.actions"
              :key="action.id"
              :title="action.title"
              class="items-center"
            >
              <button
                v-if="
                  (action.id === 'retry-atrain' && document.type === 'audio') ||
                  (!(
                    document.type === 'audio' &&
                    action.id === 'retry-conversion'
                  ) &&
                    !(
                      action.id === 'retry-conversion' &&
                      (document.isConverting || document.converted)
                    ))
                "
                class="flex items-center text-gray-700 hover:bg-silver-100 px-4 py-2 text-sm w-full text-left"
                @click="action.onClick({ action, document, index })"
              >
                <component
                  v-if="action.icon"
                  :is="action.icon"
                  class="h-4 w-4 mr-2"
                  :class="action.class"
                ></component>
                {{ action.title }}
              </button>
            </span>
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</template>
<script setup>
import {
  ArrowPathIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  EllipsisVerticalIcon,
  LockClosedIcon,
  XMarkIcon,
} from '@heroicons/vue/20/solid/index.js';
import {
  DocumentTextIcon,
  ExclamationTriangleIcon,
  SpeakerWaveIcon,
} from '@heroicons/vue/24/outline/index.js';
import { ref } from 'vue';
import { vClickOutside } from '../coding/clickOutsideDirective.js';

const emit = defineEmits(['select', 'delete']);
const props = defineProps(['documents', 'actions', 'rowClass']);
const docs = ref(props.documents);
const sorter = ref({ key: null, ascending: false });
const openMenuId = ref(null);

function toggleMenu(id) {
  // Check if the clicked menu is already open
  if (openMenuId.value === id) {
    // If the same menu is clicked, close it
    openMenuId.value = null;
  } else {
    // Otherwise, open the clicked menu
    openMenuId.value = id;
  }
}

function closeMenu() {
  openMenuId.value = null;
}

const handleOutsideClick = () => {
  let isMenuToggle = event.target.classList.contains('menu-toggle');

  // Check for parent elements with the class 'menu-toggle'
  let element = event.target;
  while (element) {
    if (element.classList && element.classList.contains('menu-toggle')) {
      isMenuToggle = true;
      break;
    }
    element = element.parentElement;
  }

  if (!isMenuToggle) {
    closeMenu();
  }
};

function dataTypeTitle(type) {
  switch (type) {
    default:
      return 'Text-based Document';
  }
}

function isMenuOpen(id) {
  return openMenuId.value === id;
}

function sort(name) {
  // new keys always sort ascending,
  // existing keys will toggle
  sorter.value.ascending =
    sorter.value.key === name ? !sorter.value.ascending : true;
  sorter.value.key = name;
  docs.value.sort((a, b) => {
    const value = String(a[name]).localeCompare(String(b[name]));
    return sorter.value.ascending ? value : value * -1;
  });
}
</script>
<style scoped>
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
