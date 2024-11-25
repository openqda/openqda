<template>
  <div :class="cn(props.class)">
    <div class="flex h-full">
      <div
        class="flex w-16 flex-shrink-0 items-center justify-center rounded-tl-md rounded-bl-md text-sm font-normal overflow-visible text-white border-t border-b border-l border-border"
        :style="getBackgroundStyle(codebook)"
      >
        <button
          class="drop-shadow-[0_1.2px_1.2px_rgba(0,0,0,0.8)] hover:underline"
          @click="openPreview({ codebook })"
        >
          {{ getInitials(codebook.name) }}
        </button>
      </div>
      <div
        class="relative flex-grow flex items-start justify-between rounded-tr-md rounded-br-md border-b border-r border-t border-gray-200 bg-surface text-foreground"
      >
        <div class="flex-1 px-4 py-2 text-sm">
          <a
            :title="codebook.name"
            :href="codebook.href"
            class="font-medium line-clamp-1"
          >
            {{ codebook.name }}
          </a>
          <p class="text-foreground/60 line-clamp-1">
            {{ codebook.description }}
          </p>
          <p class="text-foreground/60 text-xs">
            {{ codebook.creatingUserEmail }}
          </p>
          <p class="text-foreground/60">
            {{
              codebook.codes && codebook.codes.length
                ? codebook.codes.length
                : 0
            }}
            Codes
          </p>
          <div class="flex items-center space-x-2 font-mono align-center">
            <div class="flex items-center">
              <div
                class="w-2 h-2 rounded-full mr-1 shadow-xl box-decoration-slice"
                :class="
                  codebook.properties?.sharedWithPublic
                    ? 'bg-green-500 shadow-green'
                    : 'bg-red-700 shadow-red'
                "
              ></div>
              <span class="text-xs">{{
                codebook.properties?.sharedWithPublic ? 'public' : 'private'
              }}</span>
            </div>
          </div>
        </div>

        <div class="flex-shrink-0 pr-2 self-start mt-2">
          <Dropdown>
            <template #trigger>
              <button
                class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-transparent text-foreground hover:text-secondary focus:outline-none"
              >
                <span class="sr-only">Open options</span>
                <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
              </button>
            </template>
            <template #content>
              <DropdownLink as="button" @click="openEditForm(codebook)">
                Edit
              </DropdownLink>
              <DropdownLink as="button" @click="openPreview({ codebook })">
                Show Codes
              </DropdownLink>
              <DropdownLink
                v-if="isPublic"
                @click="openCreateForm(codebook)"
                as="button"
              >
                Import into project
              </DropdownLink>
              <DropdownLink as="button" @click="exportCodebook(codebook)">
                Export
              </DropdownLink>
              <DropdownLink
                v-if="!isPublic"
                @click="
                  openDeleteForm({
                    target: codebook,
                    challenge: 'name',
                    message:
                      'This is a highly destructive action! Archiving this codebook will remove all codes and selections from related sources in this project!',
                  })
                "
                as="button"
              >
                Archive
              </DropdownLink>
            </template>
          </Dropdown>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { EllipsisVerticalIcon } from '@heroicons/vue/20/solid';
import { onMounted, reactive, ref } from 'vue';
import { cn } from '../../../utils/css/cn.js';
import { useCodebookPreview } from './useCodebookPreview.js';
import Dropdown from '../../../Components/Dropdown.vue';
import DropdownLink from '../../../Components/DropdownLink.vue';
import { useCodebookUpdate } from '../../../domain/codebooks/useCodebookUpdate.js';
import { useCodebookCreate } from '../../../domain/codebooks/useCodebookCreate.js';
import { useDeleteDialog } from '../../../dialogs/useDeleteDialog.js';

const props = defineProps(['codebook', 'public', 'class']);
const { open: openPreview } = useCodebookPreview();
const { open: openEditForm } = useCodebookUpdate();
const { open: openCreateForm } = useCodebookCreate();
const { open: openDeleteForm } = useDeleteDialog();

const isPublic = ref(props.public);
const codebook = ref(props.codebook);

// Reactive state for the dialog visibility and editable fields
defineEmits(['delete', 'importCodebook']);
const url = window.location.pathname;
const segments = url.split('/');
let projectId = segments[2]; // Assuming
// Creating a reactive copy of the codes
const localCodebooks = reactive({
  codes: props.codebook.codes ? [...props.codebook.codes] : [],
});

onMounted(() => {
  localCodebooks.codes = reorderCodes(localCodebooks.codes);
});

const getInitials = (name) => {
  const words = name.split(' ');
  let initials = '';

  if (words.length === 1) {
    // If there's only one word, take the first two letters
    initials = name.substring(0, 2).toUpperCase();
  } else {
    // If there are multiple words, take the first letter of each word
    initials = words.reduce(
      (acc, namePart) => (acc += namePart.substring(0, 1).toUpperCase()),
      ''
    );
  }

  // Limit the initials to the first five characters
  return initials.substring(0, 5);
};

// Function to reorder codes
const reorderCodes = (codesArray) => {
  const orderedCodes = [];
  const childCodesMap = new Map();

  // First, separate codes into parents and children
  codesArray.forEach((code) => {
    if (code.parent_id) {
      if (!childCodesMap.has(code.parent_id)) {
        childCodesMap.set(code.parent_id, []);
      }
      childCodesMap.get(code.parent_id).push(code);
    } else {
      orderedCodes.push(code);
    }
  });

  // Then, insert child codes immediately after their parents
  return orderedCodes.flatMap((code) => [
    code,
    ...(childCodesMap.get(code.id) || []),
  ]);
};

const getBackgroundStyle = function (target) {
  // Check if there are any codes
  if (!target.codes || target.codes.length === 0) {
    // Return a default style for the empty rectangle
    return `background-color: #E5E7EB;`; // This is a light gray color, adjust as needed
  }

  // If there's only one code color, return a single color style
  if (target.codes.length === 1) {
    return `background-color: ${target.codes[0].color}`;
  }

  // Create a gradient with equal-sized stripes for each color
  const percentage = 100 / target.codes.length;
  let gradient = target.codes
    .map(
      (code, index) =>
        `${code.color} ${index * percentage}% ${(index + 1) * percentage}%`
    )
    .join(', ');

  return `background: linear-gradient(to right, ${gradient})`;
};

const exportCodebook = (codebook) => {
  window.location.href =
    '/projects/' + projectId + '/codebooks/export/' + codebook.id;
};
</script>
<style scoped>
.shadow-green {
  box-shadow: 0 0 8px 0 rgba(5, 150, 105, 0.5); /* Custom green shadow */
}

.shadow-red {
  box-shadow: 0 0 8px 0 rgba(220, 38, 38, 0.5); /* Custom red shadow */
}
</style>
