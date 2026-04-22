<script setup lang="ts">
import {
  PencilIcon,
  TrashIcon,
  ChatBubbleLeftEllipsisIcon,
  PlusIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';
import ProfileImage from '../../../Components/user/ProfileImage.vue';
import AutoForm from '../../../form/AutoForm.vue';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { useNotes } from '../../../domain/notes/useNotes';
import { useCodingEditor } from '../useCodingEditor';
import { rgbToHex } from '../../../utils/color/toHex';
import Button from '../../../Components/interactive/Button.vue';
import { variantAuthority } from '../../../utils/css/variantAuthority';
import { cn } from '../../../utils/css/cn';

/*------------------------------------------------------------------------------
 | NoteList.vue
 | Displays a list of notes for a given target (source or code). Allows editing
 | and deleting notes.
 | Target is required to ensure that updates and deletions
 | are properly associated.
 *----------------------------------------------------------------------------*/

const props = defineProps({
  notes: Array,
  target: Object,
  color: String,
  scope: String,
  type: String,
  size: {
    type: String,
    default: 'default',
  },
});

const hexCol = props.color
  ? props.color.startsWith('#')
    ? props.color
    : rgbToHex(props.color)
  : '#ffffff';

const action = ref('view');
const targetNote = ref(null);
const { createNoteSchema, createNote, editNoteSchema, updateNote, deleteNote } =
  useNotes();
const { focusSelection } = useCodingEditor();
const schema = ref();

const actions = {
  add() {
    schema.value = createNoteSchema({
      target: props.target.id,
      type: props.type,
      labels: false,
    });
    action.value = 'add';
  },
  edit(note) {
    schema.value = editNoteSchema({ labels: false, ...note });
    action.value = 'edit';
    targetNote.value = note;
  },
  delete(note) {
    action.value = 'delete';
    targetNote.value = note;
  },
};

const onSubmit = async ({ data, fn, message }) => {
  await attemptAsync(() => fn(data, props.target), message);
  action.value = 'view';
};

const textStyle = {
  class: '',
  variants: {
    size: {
      xs: 'text-sm',
      default: 'text-sm',
      md: 'text-base',
      lg: 'text-lg',
    },
  },
  defaultVariants: {
    size: 'default',
  },
};
const resolveText = variantAuthority(textStyle);
</script>

<template>
  <ul class="divide-y">
    <li
      v-if="!props.notes?.length"
      :class="cn('italic', resolveText({ size }))"
    >
      There are no linked notes yet.
    </li>
    <li
      v-for="note in props.notes"
      class="p-2 hover:bg-background/10"
      :key="note.id"
      :style="{ borderColor: hexCol }"
    >
      <div
        :class="
          cn(
            'w-full flex items-center justify-between tracking-wider font-semibold',
            resolveText({ size })
          )
        "
      >
        <div class="flex items-center gap-2">
          <span class="font-semibold">
            <ChatBubbleLeftEllipsisIcon class="w-3 h-3 inline-block" />
            Note
          </span>
          <span class="flex">
            <a
              v-if="note.type === 'selection' && note.scope"
              href=""
              @click.prevent="focusSelection(note)"
              class="font-mono hover:underline"
              >scope: {{ note.scope.start }}:{{ note.scope.end }}</a
            >
            <ProfileImage
              v-if="note.user"
              :src="note.user.profile_photo_url"
              :name="note.user.name"
              class="w-3 h-3 ms-1"
            />
          </span>
        </div>
        <!-- Buttons -->
        <div class="flex items-center">
          <button
            v-if="action !== 'edit' || targetNote.id !== note.id"
            class="p-2 me-1"
            @click="actions.edit(note)"
            title="Edit this note"
          >
            <PencilIcon class="w-4 h-4 hover:text-primary" />
          </button>
          <button
            class="p-2 me-1"
            @click="actions.delete(note)"
            title="Delete this note"
          >
            <TrashIcon class="w-4 h-4 hover:text-destructive" />
          </button>
        </div>
      </div>

      <div v-if="action === 'edit' && targetNote.id === note.id">
        <AutoForm
          id="editNoteForm"
          :schema="schema"
          show-submit
          show-cancel
          @submit="
            (data) =>
              onSubmit({
                data,
                fn: updateNote,
                message: 'Note successfully created',
              })
          "
          @cancel="action = 'view'"
        />
      </div>
      <p
        v-else
        :class="
          cn(
            'cursor-text overflow-x-scroll py-4 md:py-2 lg:py-0',
            resolveText({ size })
          )
        "
      >
        {{ note.content }}
      </p>
      <div
        v-if="action === 'delete' && targetNote.id === note.id"
        :class="
          cn('border border-destructive p-2 rounded-md', resolveText({ size }))
        "
      >
        <div class="block font-semibold text-destructive">Delete this note</div>
        <p class="my-3">
          Are you sure you want to delete this note? This action cannot be
          undone.
        </p>
        <div class="flex items-center justify-between">
          <Button variant="outline" size="sm" @click="action = 'view'"
            >Cancel</Button
          >
          <Button
            variant="destructive"
            size="sm"
            @click="
              onSubmit({
                data: note,
                fn: deleteNote,
                message: 'Note successfully deleted',
              })
            "
            >Delete</Button
          >
        </div>
      </div>
    </li>
    <li v-if="action === 'add'">
      <AutoForm
        id="addNoteForm"
        :schema="schema"
        show-submit
        show-cancel
        @submit="
          (data) =>
            onSubmit({
              data,
              fn: createNote,
              message: 'Note successfully created',
            })
        "
        @cancel="action = 'view'"
      />
    </li>
    <li v-if="action === 'view'">
      <Button
        variant="outline-secondary"
        size="sm"
        class="w-full"
        @click="actions.add()"
      >
        <PlusIcon class="w-4 h-4" /> Add Note
      </Button>
    </li>
  </ul>
</template>

<style scoped></style>
