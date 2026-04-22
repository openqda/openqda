<script setup lang="ts">
import {
  ArrowsRightLeftIcon,
  QueueListIcon,
  TrashIcon,
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
import NoteList from '../tree/NoteList.vue';
import { attemptAsync } from '../../../Components/notification/attemptAsync';

const { getMemberBy } = useUsers();
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
    if (deleteTarget.value) {
      return deleteTarget.value === s;
    }
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
  deleteTarget.value = null;
  reassign.value = reassign.value === selection ? null : selection;
};

// ---------------------------------------------
// DELETING SELECTION#
// ---------------------------------------------
const deleteTarget = ref(null);
const startDeleting = (selection) => {
  manageNotes.value = null;
  reassign.value = null;
  deleteTarget.value = deleteTarget.value === selection ? null : selection;
};

// ---------------------------------------------
// NOTES MANAGEMENT
// ---------------------------------------------
const manageNotes = ref(null);
const startManageNotes = (selection) => {
  reassign.value = null;
  deleteTarget.value = null;
  manageNotes.value = manageNotes.value === selection ? null : selection;
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
    toDelete.value = null;
    manageNotes.value = null;
    query.value = '';
    close();
    emit('close');
  }
};
const onDeleteSelection = async () => {
  await attemptAsync(
    () => deleteSelection(deleteTarget),
    'Selection successfully deleted'
  );
  close();
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
                <ProfileImage
                  v-if="getMemberBy(selection.creating_user_id)"
                  size="compact"
                  :name="getMemberBy(selection.creating_user_id)?.name"
                  :src="
                    getMemberBy(selection.creating_user_id)?.profile_photo_url
                  "
                />
              </div>
              <button
                v-if="toDeleteSize > 0"
                @click.prevent="startManageNotes(selection)"
                :class="
                  cn(
                    'flex items-center gap-0.5 text-sm hover:text-primary',
                    selection === manageNotes
                      ? 'text-primary'
                      : 'hover:text-primary'
                  )
                "
                :title="
                  manageNotes
                    ? 'Cancel managing notes'
                    : 'Manage notes for this selection'
                "
              >
                <ChatBubbleLeftEllipsisIcon :class="'w-4 h-4'" />
                <span>{{ selection.notes?.length ?? 0 }}</span>
              </button>
              <button
                v-if="toDeleteSize > 0"
                :class="
                  cn(
                    selection === reassign
                      ? 'text-primary'
                      : 'hover:text-primary'
                  )
                "
                :title="
                  reassign
                    ? 'Cancel reassign for this selection'
                    : 'Reassign another code to this selection'
                "
                @click.prevent="startReassign(selection)"
              >
                <ArrowsRightLeftIcon class="w-4 h-4" />
              </button>
              <button
                size="sm"
                title="Delete this selection"
                :class="
                  cn(
                    selection === deleteTarget
                      ? 'text-destructive'
                      : 'hover:text-destructive'
                  )
                "
                @click.prevent="startDeleting(selection)"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
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
      <div class="font-semibold">Manage noes for this selection</div>
      <NoteList
        :notes="manageNotes.notes"
        :target="manageNotes"
        type="selection"
        :scope="manageNotes.scope"
      />
    </div>

    <div
      v-else-if="deleteTarget"
      class="p-3 border border-destructive rounded-md text-sm"
    >
      <p>
        You are about to delete this selection. This action cannot be undone.
      </p>
      <div class="flex items-center justify-between">
        <Button variant="outline" size="sm" @click="deleteTarget = null"
          >Cancel</Button
        >
        <Button variant="destructive" size="sm" @click="onDeleteSelection"
          >Delete</Button
        >
      </div>
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
