<script setup lang="ts">
import {
  ArrowsRightLeftIcon,
  QueueListIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/vue/24/solid';
import { ChatBubbleLeftEllipsisIcon } from '@heroicons/vue/24/outline';
import Button from '../../../Components/interactive/Button.vue';
import { cn } from '../../../utils/css/cn';
import { ref, computed, watch } from 'vue';
import InputField from '../../../form/InputField.vue';
import { vClickOutside } from '../../../utils/vue/clickOutsideDirective';
import CodingContextMenuItem from './CodingContextMenuItem.vue';
import { useSelections } from '../selections/useSelections';
import { useContextMenu } from './useContextMenu';
import { useCodes } from '../../../domain/codes/useCodes';
import { useRange } from '../useRange';
import { whitespace } from '../../../utils/regex';
import { useUsers } from '../../../domain/teams/useUsers';
import { useInvivoText } from '../useInvivoText';
import ProfileImage from '../../../Components/user/ProfileImage.vue';
import AutoForm from '../../../form/AutoForm.vue';
import { useNotes } from '../../../domain/notes/useNotes';
import NoteRenderer from '../../../domain/notes/NoteRenderer.vue';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
const { getMemberBy } = useUsers();
const { createNoteSchema, createNote } = useNotes();
const { prevRange, range, text: rangeText } = useRange();
const { close, isOpen, top, left, width, maxHeight } = useContextMenu();
const { codes } = useCodes();
const { toDelete, deleteSelection } = useSelections();
const { set } = useInvivoText();
const emit = defineEmits([
  'code-selected',
  'code-deleted',
  'close',
  'code-to-create',
]);

const query = ref('');
const toDeleteSize = ref(0);

watch(toDelete, (entries) => {
  const len = entries?.length;
  toDeleteSize.value = len ?? 0;
});

const selections = computed(() => {
  const list = toDelete.value ?? [];
  return list.filter((s) => {
    if (reassign.value) {
      return reassign.value === s;
    }
    if (manageNotes.value) {
      return manageNotes.value === s;
    }
    return true;
  });
});

// ---------------------------------------------
// REASSIGN CODE TO SELECTION
// ---------------------------------------------
const reassign = ref(null);
const startReassign = (selection) => {
  manageNotes.value = null;
  reassign.value = reassign.value === selection ? null : selection;
};
// ---------------------------------------------
// NOTES MANAGEMENT
// ---------------------------------------------
const addNote = ref(false);
const manageNotes = ref(null);
const newNoteSchema = ref(null);
const startManageNotes = (selection) => {
  reassign.value = null;
  manageNotes.value = manageNotes.value === selection ? null : selection;
  newNoteSchema.value = createNoteSchema({
    target: selection.id,
    type: 'selection',
    scope: `${selection.start}:${selection.end}`,
  });
};

const filteredCodes = computed(() => {
  const searchQuery = query.value.toLowerCase().replace(whitespace, '');
  if (searchQuery.length < 2) return codes.value;
  const filterFn = (code) => {
    if (!code) return false;
    if (code.name.toLowerCase().replace(whitespace, '').includes(searchQuery)) {
      return true;
    }
    return (code.children ?? []).some(filterFn);
  };

  return codes.value.filter(filterFn);
});

const onClose = () => {
  if (isOpen.value) {
    reassign.value = null;
    manageNotes.value = null;
    query.value = '';
    close();
    emit('close');
  }
};

const createInVivo = () => {
  const txt = rangeText?.value;
  if (!txt?.length) return;
  onClose();
  setTimeout(() => {
    set(txt);
  }, 100);
};
</script>

<template>
  <div
    v-click-outside="{ callback: onClose }"
    id="contextMenu"
    :class="
      cn(
        'fixed p-3 z-50 bg-surface border-background border-4 max-h-screen mt-1 overflow-auto rounded-md shadow-lg overflow-y-scroll',
        !isOpen && 'hidden'
      )
    "
    :style="{
      top: `${top}px`,
      left: `${left}px`,
      width: `${width}px`,
      maxHeight: maxHeight ? `${maxHeight}px` : `100vh`,
    }"
  >
    <div v-if="range?.length" class="mb-6">
      <Button variant="outline-secondary" @click="createInVivo"
        >Create In-Vivo Code</Button
      >
    </div>

    <div v-if="toDeleteSize" class="mb-6 flex flex-col gap-2">
      <div class="block w-full text-xs font-semibold">
        Edit linked selections
      </div>
      <div
        class="text-sm flex flex-col gap-2"
        v-for="selection in selections"
        :key="selection.id"
      >
        <div class="contents">
          <div class="border-border border-t">
            <div class="flex items-baseline my-2 gap-2">
              <div class="flex items-center text-xs font-semibold grow gap-2">
                <QueueListIcon class="w-3 h-3" />
                <span>Selection</span>
                <span> {{ selection.start }}:{{ selection.end }} </span>
              </div>
              <ProfileImage
                v-if="getMemberBy(selection.creating_user_id)"
                class="w-4 h-4"
                :name="`by ${getMemberBy(selection.creating_user_id).name}`"
                :src="getMemberBy(selection.creating_user_id).profile_photo_url"
              />
              <button
                v-if="toDeleteSize > 0"
                @click.prevent="startManageNotes(selection)"
                class="flex items-center gap-0.5 text-sm hover:text-primary"
                :title="
                  manageNotes
                    ? 'Cancel managing notes'
                    : 'Manage notes for this selection'
                "
              >
                <XMarkIcon v-show="selection === manageNotes" class="w-4 h-4" />
                <ChatBubbleLeftEllipsisIcon
                  v-show="selection !== manageNotes"
                  class="w-4 h-4"
                />
                <span v-show="selection !== manageNotes">{{
                  selection.notes?.length ?? 0
                }}</span>
              </button>
              <button
                v-if="toDeleteSize > 0"
                :title="
                  reassign
                    ? 'Cancel reassign for this selection'
                    : 'Reassign another code to this selection'
                "
                @click.prevent="startReassign(selection)"
              >
                <XMarkIcon v-show="selection === reassign" class="w-4 h-4" />
                <ArrowsRightLeftIcon
                  v-show="selection !== reassign"
                  class="w-4 h-4 hover:text-primary"
                />
              </button>
              <Button
                size="sm"
                title="Delete this selection"
                variant="destructive"
                class="p-2"
                @click.prevent="deleteSelection(selection) && close()"
              >
                <TrashIcon class="w-4 h-4" />
              </Button>
            </div>
            <p class="line-clamp-2">{{ selection.text }}</p>
          </div>
          <div
            class="p-1 my-1 rounded text-xs"
            :style="`background: ${selection.code.color};`"
          >
            {{ selection.code.name }}
          </div>
        </div>
      </div>
    </div>

    <div v-if="!codes?.length" class="text-sm italic text-foreground/80">
      You seem to have no codes created yet.
    </div>

    <div v-if="manageNotes">
      <div
        v-for="note in manageNotes.notes ?? []"
        :key="note.id"
        class="my-2 border-b"
      >
        <NoteRenderer :note="note" :target="manageNotes" />
      </div>
      <div
        v-if="!manageNotes.notes?.length && !addNote"
        class="text-sm italic text-foreground/80"
      >
        There are no notes for this selection yet.
      </div>

      <div v-if="addNote">
        <div class="text-sm font-semibold">
          Add new note for selection {{ manageNotes.start }}:{{
            manageNotes.end
          }}
        </div>
        <AutoForm
          id="createNoteForm"
          :schema="newNoteSchema"
          @submit="
            (data) =>
              attemptAsync(
                () => createNote(data, manageNotes),
                'Note successfully created'
              )
          "
          @cancel="
            manageNotes = null;
            addNote = null;
          "
          @close="
            addNote = null;
            manageNotes = null;
          "
        />
      </div>
      <Button
        v-else
        variant="outline-secondary"
        class="mt-2 w-full"
        @click.stop="addNote = true"
      >
        Add Note
      </Button>
    </div>

    <div
      v-else-if="
        codes?.length && (!toDeleteSize || reassign || prevRange?.length)
      "
    >
      <div class="block w-full text-xs font-semibold">
        {{
          reassign
            ? `Reassign another code to ${reassign.start}:${reassign.end}`
            : 'Assign a new code to selection'
        }}
      </div>

      <!-- text input field to filter by name -->
      <InputField
        v-model="query"
        placeholder="Filter codes by name"
        class="placeholder-foreground/50"
      />
      <ul>
        <CodingContextMenuItem
          v-for="code in filteredCodes"
          :reassign="reassign"
          :key="code.id"
          :code="code"
          :parent="null"
        />
      </ul>
    </div>
  </div>
</template>

<style scoped></style>
