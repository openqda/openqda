<template>
  <table :class="cn('w-full border-collapse', props.fixed && 'table-fixed')">
    <thead>
      <tr class="align-middle" :class="props.rowClass">
        <th class="w-5" v-if="fieldsVisible.lock"></th>
        <th
          v-for="field in headerFields.filter(
            (field) => fieldsVisible[field.key]
          )"
          :key="field.key"
          scope="col"
          :class="
            cn(
              'text-center text-xs font-normal text-foreground/50 sm:pl-0',
              field.class
            )
          "
        >
          <a
            href
            @click.prevent="() => sort(field.key)"
            class="flex justify-center tracking-wider"
            :title="field.title"
          >
            <span>{{ field.label }}</span>
            <span>
              <ChevronUpIcon
                class="h-4 w-4 text-foreground/50"
                v-if="sorter.key === field.key && sorter.ascending === true"
              />
              <ChevronDownIcon
                class="h-4 w-4 text-foreground/50"
                v-if="sorter.key === field.key && sorter.ascending === false"
              />
            </span>
          </a>
        </th>
        <th
          scope="col"
          v-if="fieldsVisible.actions"
          v-show="$props.actions?.length"
          class="w-6 text-end text-xs font-medium uppercase text-foreground/50 sm:pl-0"
        >
          <span class="sr-only">Actions</span>
        </th>
        <slot name="custom-head" />
      </tr>
    </thead>
    <tbody>
      <tr
        v-for="(document, index) in docs"
        :key="document.id"
        :class="
          cn(
            'text-sm',
            document.selected || hover === index
              ? 'bg-secondary/20'
              : 'hover:text-secondary',
            !document.converted || document.failed
              ? 'text-foreground/20'
              : 'text-foreground',
            props.rowClass
          )
        "
      >
        <td class="text-center" v-if="fieldsVisible.lock">
          <LockOpenIcon
            v-if="!document.variables?.isLocked"
            class="w-4 h-4 text-foreground/20"
          />
          <LockClosedIcon
            v-if="document.variables?.isLocked"
            class="w-4 h-4 text-secondary/60"
          />
        </td>
        <td
          v-if="fieldsVisible.name"
          :class="
            cn(
              'py-4 w-auto rounded-xl',
              hover === index ? 'break-all' : 'truncate',
              openMenuId === document.id ? 'font-semibold' : 'font-normal'
            )
          "
          :colspan="hover === index ? (props.colspan ?? 5) : undefined"
        >
          <a
            @mouseenter="focusOnHover && (hover = index)"
            @mouseleave="focusOnHover && (hover = -1)"
            @touchstart="focusOnHover && (hover = index)"
            @touchend="focusOnHover && (hover = -1)"
            @click="
              document.converted &&
              !document.selected &&
              emit('select', document, index)
            "
            :title="
              hover === index
                ? 'File already open'
                : `Open ${document.name} in editor`
            "
            :class="
              cn(
                'py-3 tracking-wide',
                document.converted && !document.failed
                  ? ''
                  : 'cursor-not-allowed pointer-events-none',
                !document.selected && 'cursor-pointer'
              )
            "
          >
            {{ document.name }}
          </a>
        </td>

        <td class="py-2" v-if="fieldsVisible.type && hover !== index">
          <!--
                <div
                  v-if="
                    !document.isQueued &&
                    !document.isUploading &&
                    !document.converted &&
                    !document.failed
                  "
                  title="There was an error during upload or conversion. Please retry or delete this file."
                  class="inline-flex justify-center w-full p-1 clickable"
                >
                  <ExclamationTriangleIcon
                    class="w-5 h-5 text-destructive! rounded-md font-semibold"
                  />
                </div>
                -->
          <div
            v-if="document.isQueued"
            title="Queued for uploading"
            class="inline-flex justify-center w-full p-1"
          >
            <ClockIcon class="w-5 h-5 text-secondary" />
          </div>
          <div
            v-else-if="document.isUploading"
            :title="`Uploading file: ${document.progress ?? 0}%`"
            class="inline-flex justify-center w-full p-1 text-xs"
          >
            <CloudArrowUpIcon class="w-5 h-5 text-secondary" />
          </div>
          <div
            v-else-if="!document.converted && !document.failed"
            class="inline-flex justify-center w-full p-1"
            :title="`Converting file in the background${conversionState(document)}. You may safely leave the page and come back later.`"
          >
            <div class="animate-spin mr-1">
              <ArrowPathIcon class="w-5 h-5 text-secondary"></ArrowPathIcon>
            </div>
            Converting
          </div>
          <div
            v-else-if="document.failed"
            title="There was an error during upload or conversion. Please retry or delete this file."
            class="inline-flex justify-center w-full p-1 clickable"
          >
            <ExclamationTriangleIcon
              class="w-5 h-5 text-destructive! rounded-md font-semibold"
            />
          </div>
          <!-- TODO make this open-close impl -->
          <div
            v-else
            :title="dataTypeTitle(document.type)"
            class="w-full text-center"
          >
            <DocumentTextIcon
              v-if="document.type === 'text'"
              class="h-4 w-4 text-foreground ml-auto mr-auto"
            />
            <SpeakerWaveIcon
              v-if="document.type === 'audio'"
              class="h-4 w-4 text-foreground ml-auto mr-auto"
            />
          </div>
        </td>
        <td
          class="py-2 text-center"
          v-if="fieldsVisible.date && hover !== index"
        >
          {{ document.date }}
        </td>
        <td
          class="py-2 text-center tracking-wider"
          v-if="fieldsVisible.user && hover !== index"
        >
          <div>
            <ProfileImage
              v-if="document.userPicture"
              class="w-4 h-4"
              :name="document.user"
              :email="document.userEmail"
              :src="document.userPicture"
            />
          </div>
        </td>
        <td
          v-if="hover !== index"
          v-show="$props.actions?.length"
          class="py-2 text-center tracking-wider justify-center align-middle items-center relative"
        >
          <Button
            variant="outline"
            @click="toggleMenu(document.id)"
            :class="cn(openMenuId === document.id && 'border-primary')"
            size="sm"
          >
            <EllipsisVerticalIcon
              class="w-4 h-4 menu-toggle z-0"
            ></EllipsisVerticalIcon>
          </Button>

          <div
            v-show="isMenuOpen(document.id)"
            v-click-outside="{ callback: handleOutsideClick }"
            class="absolute right-0 mt-2 py-2 w-60 center bg-surface rounded-md border-2 border-border z-20"
          >
            <span
              v-for="action in $props.actions"
              :key="action.id"
              :title="action.title"
              class="items-center"
            >
              <button
                v-if="action.visible(document)"
                class="flex items-center text-foreground hover:bg-foreground/20 px-4 py-2 text-sm w-full text-left"
                @click="action.onClick({ action, document, index })"
              >
                <component
                  v-if="action.icon"
                  :is="action.icon"
                  :class="cn('h-4 w-4 mr-2', action.class)"
                ></component>
                {{ action.title }}
              </button>
            </span>
          </div>
        </td>
        <slot
          v-if="$slots['custom-cells']"
          name="custom-cells"
          :source="document"
          :index="index"
        />
      </tr>
    </tbody>
  </table>
</template>

<script setup>
/**
 * FileList is a generic list component that
 * renders given documents, representing files
 * with specific actions.
 */

import {
  ArrowPathIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  EllipsisVerticalIcon,
  LockClosedIcon,
} from '@heroicons/vue/20/solid/index.js';
import { LockOpenIcon } from '@heroicons/vue/24/outline';
import {
  CloudArrowUpIcon,
  ClockIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  SpeakerWaveIcon,
} from '@heroicons/vue/24/outline/index.js';
import { computed, ref } from 'vue';
import { vClickOutside } from '../../utils/vue/clickOutsideDirective.js';
import { cn } from '../../utils/css/cn.js';
import ProfileImage from '../user/ProfileImage.vue';
import Button from '../interactive/Button.vue';

const emit = defineEmits(['select', 'delete']);
const props = defineProps([
  'documents',
  'actions',
  'rowClass',
  'fields',
  'fixed',
  'colspan',
  'focusOnHover',
]);
const docs = computed(() => props.documents.filter(Boolean));
const sorter = ref({ key: null, ascending: false });
const openMenuId = ref(null);
const headerFields = ref([
  {
    label: 'File',
    key: 'name',
    title: 'Sort by name',
    class: 'w-4/6',
  },
  {
    label: 'Type',
    key: 'type',
    title: 'Sort by type',
    class: 'w-1/5',
  },
  {
    label: 'Date',
    key: 'date',
    title: 'Sort by last edited date',
    class: 'w-2/6',
  },
  {
    label: 'By',
    key: 'user',
    title: 'Sort by uploader',
    class: 'w-6',
  },
]);
const fieldsVisible = ref({
  lock: true,
  name: true,
  type: true,
  date: true,
  user: true,
  actions: true,
  ...(props.fields ?? {}),
});
const hover = ref(-1);

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

function conversionState(document) {
  if (!document.variables || !document.variables.transcription_job_status) {
    return '';
  }

  switch (document.variables.transcription_job_status) {
    case 'uploading':
      return ' (5%)';
    case 'processing':
      return ' (15%)';
    case 'downloading':
      return ' (60%)';
    case 'deleting':
      return ' (90%)';
    case 'failed':
      return ' (failed; please retry or remove this file)';
    default:
      return '';
  }
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
    case 'audio':
      return 'Audio file transcription';
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
