<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { cn } from '../../../utils/css/cn';
import { useCodeTree } from './useCodeTree';
import { useCodes } from '../../../domain/codes/useCodes';
import { useNotes } from '../../../domain/notes/useNotes';
import {
  ArrowPathIcon,
  BarsArrowDownIcon,
  ChevronRightIcon,
  EllipsisVerticalIcon,
  EyeIcon,
  EyeSlashIcon,
  PencilIcon,
  PlusIcon,
} from '@heroicons/vue/24/solid';
import { ChatBubbleLeftEllipsisIcon } from '@heroicons/vue/24/outline';
import Button from '../../../Components/interactive/Button.vue';
import { TrashIcon } from '@heroicons/vue/24/outline';
import DropdownLink from '../../../Components/DropdownLink.vue';
import Dropdown from '../../../Components/Dropdown.vue';
import { changeOpacity } from '../../../utils/color/changeOpacity';
import { useUsers } from '../../../domain/teams/useUsers';
import SelectionList from './SelectionList.vue';
import { Collapse } from 'vue-collapsed';
import { asyncTimeout } from '../../../utils/asyncTimeout';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { useDeleteDialog } from '../../../dialogs/useDeleteDialog';
import { useRenameDialog } from '../../../dialogs/useRenameDialog';
import ContrastText from '../../../Components/text/ContrastText.vue';
import { useRange } from '../useRange';
import { rgbToHex } from '../../../utils/color/toHex';
import { useSelections } from '../selections/useSelections';
import FormDialog from '../../../dialogs/FormDialog.vue';
import NoteList from './NoteList.vue';

const { createCode, toggleCode, createCodeSchema, getCodebook } = useCodes();
const { createNote, createNoteSchema } = useNotes();
const { collapsed, toggleCollapse } = useCodeTree();
const { getMemberBy } = useUsers();
const Selections = useSelections();

const props = defineProps({
  code: Object,
  class: String,
  sorting: Boolean,
  showDetails: Boolean,
});

//------------------------------------------------------------------------
// Collapse
//------------------------------------------------------------------------
const open = computed(() => props.sorting || collapsed.value[props.code.id]);
const toggle = () => {
  const newState = toggleCollapse(props.code.id);
  // if collapse closed then also close
  // text segments
  if (!newState) {
    closeTexts();
  }
};

//------------------------------------------------------------------------
// TEXTS (SELECTIONS)
//------------------------------------------------------------------------
const showTexts = ref(false);
const textCount = ref(0);
const textSelectionsCount = (code) => {
  let count = code.text?.length ?? 0;
  if (code.children?.length) {
    code.children.forEach((child) => {
      count += textSelectionsCount(child);
    });
  }

  return count;
};
textCount.value = computed(() => {
  return textSelectionsCount(props.code);
});
const openTexts = () => {
  showTexts.value = true;
};
const closeTexts = () => {
  showTexts.value = false;
};
const sortedTexts = computed(() => {
  if (!props.code.text?.length) return [];
  return props.code.text
    .toSorted((a, b) => a.start - b.start)
    .map((txt) => {
      txt.user = getMemberBy(txt.createdBy);
      return txt;
    });
});

//------------------------------------------------------------------------
// NOTES (Memos)
//------------------------------------------------------------------------
const showNotes = ref(false);
const notesCount = ref(0);
const textNotesCount = (code) => {
  let count = code.notes?.length ?? 0;
  if (code.children?.length) {
    code.children.forEach((child) => {
      count += textNotesCount(child);
    });
  }

  return count;
};
notesCount.value = computed(() => {
  return textNotesCount(props.code);
});
const openNotes = () => {
  showNotes.value = true;
};
const closeNotes = () => {
  showNotes.value = false;
};
const sortedNotes = computed(() => {
  if (!props.code.notes?.length) return [];
  return props.code.notes.map((note) => {
    note.user = getMemberBy(note.creating_user_id);
    return note;
  });
});

//------------------------------------------------------------------------
// VISIBILITY
//------------------------------------------------------------------------
const toggling = reactive({});
const handleCodeToggle = async (code) => {
  toggling[code.id] = true;
  await asyncTimeout(100);
  await attemptAsync(() => toggleCode(code));
  toggling[code.id] = false;
};

//------------------------------------------------------------------------
// DIALOGS
//------------------------------------------------------------------------
const { open: openDeleteDialog } = useDeleteDialog();
const { open: openRenameDialog } = useRenameDialog();
const editCode = (target) => {
  const schema = createCodeSchema({
    title: target.name,
    description: target.description,
    color: rgbToHex(target.color),
  });
  schema.id = {
    type: String,
    label: null,
    formType: 'hidden',
    defaultValue: target.id,
  };
  delete schema.codebookId;
  openRenameDialog({ id: `edit-code-${target.id}`, target, schema });
};

//------------------------------------------------------------------------
// CREATE SUBCODE
//------------------------------------------------------------------------
const createNewCodeSchema = ref();
const openCreateSubcodeDialog = (parent) => {
  const schema = createCodeSchema({
    codebooks: [getCodebook(parent.codebook)],
    codes: [parent],
    parent,
  });
  schema.color.defaultValue = rgbToHex(parent.color);
  createNewCodeSchema.value = schema;
};

//------------------------------------------------------------------------
// CRATE NOTE
//------------------------------------------------------------------------
const createNewNoteSchema = ref();
const openCreateNoteDialog = (code) => {
  createNewNoteSchema.value = createNoteSchema({
    target: code.id,
    type: 'code',
  });
};

//------------------------------------------------------------------------
// Range
//------------------------------------------------------------------------
const { range } = useRange();
</script>

<template>
  <div class="w-full">
    <div class="flex items-center w-auto">
      <!-- collapse button -->
      <Button
        v-if="code.children?.length"
        :title="open ? 'Hide children' : 'Show children'"
        variant="default"
        size="sm"
        :disabled="sorting"
        class="bg-transparent text-foreground! hover:text-background w-4 p-0! rounded"
        @click="toggle()"
      >
        <ChevronRightIcon
          :class="
            cn(
              'w-3 h-3 transition-all duration-300 transform',
              open && 'rotate-90'
            )
          "
        />
      </Button>
      <span class="w-4 h-4" v-else></span>

      <!-- code name -->
      <div
        :class="
          cn(
            'w-full tracking-wide rounded-md px-2 py-1 text-sm text-foreground dark:text-background group hover:shadow',
            sorting && 'cursor-grab'
          )
        "
        :style="`background: ${changeOpacity(code.color ?? 'rgba(0,0,0,1)', 1)};`"
      >
        <button
          v-if="!sorting && range?.length"
          @click.prevent="Selections.select({ code })"
          :title="`Assign ${code.name} to selection ${range.start}:${range.end}`"
          :class="
            cn(
              'w-full h-full text-left flex items-center',
              code.active
                ? 'hover:font-semibold'
                : 'cursor-not-allowed text-foreground/20'
            )
          "
        >
          <ContrastText>{{ code.name }}</ContrastText>
          <ContrastText
            class="text-xs ms-auto font-normal hidden group-hover:inline"
            >Assign to {{ range.start }}:{{ range.end }}</ContrastText
          >
        </button>
        <ContrastText v-else>{{ code.name }}</ContrastText>
        <ContrastText
          v-if="props.showDetails && code.description"
          class="block my-1 text-xs"
          >Description: {{ code.description }}</ContrastText
        >
      </div>

      <div class="flex justify-between items-center gap-2">
        <!-- show texts -->
        <Button
          :title="showTexts ? 'Hide selections list' : 'Show selections list'"
          variant="ghost"
          size="sm"
          :class="
            cn(
              'px-1! py-1! my-0! w-8 text-xs hover:text-secondary',
              showTexts && 'text-secondary'
            )
          "
          :disabled="!code.text?.length"
          @click.prevent="showTexts ? closeTexts() : openTexts()"
        >
          <BarsArrowDownIcon class="w-4 -h-4" />
          <span class="text-xs">{{
            open ? (code.text?.length ?? 0) : textCount
          }}</span>
        </Button>

        <!-- show notes -->
        <Button
          :title="
            showNotes
              ? 'Hide notes attached this code'
              : 'Show notes attached to this code'
          "
          variant="ghost"
          size="sm"
          :class="
            cn(
              'px-1! py-1! my-0! w-8 text-xs hover:text-secondary',
              showNotes && 'text-secondary'
            )
          "
          :disabled="!code.notes?.length"
          @click.prevent="showNotes ? closeNotes() : openNotes()"
        >
          <ChatBubbleLeftEllipsisIcon class="w-4 -h-4" />
          <span class="text-xs">{{
            open ? (code.notes?.length ?? 0) : notesCount
          }}</span>
        </Button>

        <!-- visibility -->
        <button
          class="p-0 m-0 text-foreground/80"
          @click.prevent="handleCodeToggle(code)"
          :title="
            code.active
              ? 'Code visible, click to hide'
              : 'Code hidden, click to show'
          "
        >
          <ArrowPathIcon
            v-if="toggling[code.id]"
            class="w-4 h-4 animate-spin text-foreground/50"
          />
          <EyeSlashIcon
            v-else-if="code.active === false"
            class="w-4 h-4 text-foreground/50"
          />
          <EyeIcon v-else class="w-4 h-4" />
        </button>

        <!-- code menu -->
        <Dropdown :disabled="sorting">
          <template #trigger>
            <button
              :disabled="sorting"
              :class="
                cn(
                  'p-2 md:p-1 lg:p-0 m-0 text-foreground rounded hover:bg-foreground/10',
                  sorting && 'cursor-not-allowed text-foreground/50'
                )
              "
            >
              <EllipsisVerticalIcon class="w-4 h-4" />
            </button>
          </template>
          <template #content>
            <DropdownLink as="button" @click.prevent="editCode(code)">
              <div class="flex items-center">
                <PencilIcon class="w-4 h-4 me-2" />
                <span>Edit code</span>
              </div>
            </DropdownLink>
            <DropdownLink as="button">
              <FormDialog
                :schema="createNewCodeSchema"
                :title="`Create a subcode for ${code.name}`"
                :submit="createCode"
              >
                <template #trigger="{ trigger }">
                  <div
                    @click="trigger(() => openCreateSubcodeDialog(code))"
                    class="flex items-center"
                  >
                    <PlusIcon class="w-4 h-4 me-2" />
                    <span>Add subcode</span>
                  </div>
                </template>
              </FormDialog>
            </DropdownLink>
            <DropdownLink as="button">
              <FormDialog
                :schema="createNewNoteSchema"
                :title="`Create a new note for ${code.name}`"
                :submit="
                  (data) =>
                    attemptAsync(
                      () => createNote(data, code),
                      'Note successfully created'
                    )
                "
              >
                <template #trigger="{ trigger }">
                  <div
                    @click="trigger(() => openCreateNoteDialog(code))"
                    class="flex items-center"
                  >
                    <ChatBubbleLeftEllipsisIcon class="w-4 h-4 me-2" />
                    <span>Add Note</span>
                  </div>
                </template>
                <template #intro>
                  <div
                    class="w-full tracking-wide rounded-md px-1 py-1 text-sm text-foreground dark:text-background group hover:shadow-sm mb-4"
                    :style="`background: ${changeOpacity(code.color ?? 'rgba(0,0,0,1)', 1)};`"
                  >
                    <span class="bg-surface text-foreground px-1 rounded">{{
                      code.name
                    }}</span>
                  </div>
                </template>
              </FormDialog>
            </DropdownLink>
            <DropdownLink
              as="button"
              @click.prevent="
                openDeleteDialog({
                  target: code,
                  challenge: 'name',
                  message:
                    'This will also delete all selections across all sources within this project, that are related to this code!',
                })
              "
            >
              <div class="flex">
                <TrashIcon class="w-4 h-4 me-2 text-destructive" />
                <span>Delete this code</span>
              </div>
            </DropdownLink>
          </template>
        </Dropdown>
      </div>
    </div>

    <div
      :class="cn('me-2', (showTexts || showNotes) && 'border-r')"
      :style="`border-color: ${changeOpacity(code.color ?? 'rgba(0,0,0,1)', 1)};`"
    >
      <!-- TEXT Selections -->
      <Collapse :when="code && textCount && showTexts">
        <div
          :style="`border-color: ${changeOpacity(code.color ?? 'rgba(0,0,0,1)', 1)};`"
          class="bg-surface border text-sm ms-4 me-4 my-1 rounded"
        >
          <SelectionList
            :texts="sortedTexts"
            :color="code.color ?? 'rgba(0,0,0,1)'"
          />
        </div>
      </Collapse>

      <!-- Notes -->
      <Collapse :when="code && notesCount && showNotes">
        <div
          :style="`border-color: ${changeOpacity(code.color ?? 'rgba(0,0,0,1)', 1)};`"
          class="bg-surface border text-sm ms-4 me-4 my-1 rounded"
        >
          <NoteList
            :notes="sortedNotes"
            :code="code"
            :color="code.color ?? 'rgba(0,0,0,1)'"
          />
        </div>
      </Collapse>
    </div>
  </div>
</template>

<style scoped></style>
