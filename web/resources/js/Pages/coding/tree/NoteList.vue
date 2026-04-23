<script setup lang="ts">
import {
  Bars3BottomLeftIcon,
  PencilIcon,
  TrashIcon,
  ChatBubbleLeftEllipsisIcon,
  PlusIcon,
  UsersIcon,
  UserIcon,
} from '@heroicons/vue/24/outline';
import { ref } from 'vue';
import ProfileImage from '../../../Components/user/ProfileImage.vue';
import AutoForm from '../../../form/AutoForm.vue';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { useNotes } from '../../../domain/notes/useNotes';
import { rgbToHex } from '../../../utils/color/toHex';
import Button from '../../../Components/interactive/Button.vue';
import { variantAuthority } from '../../../utils/css/variantAuthority';
import { cn } from '../../../utils/css/cn';
import { useUsers } from '../../../domain/teams/useUsers';

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
    default: 'sm',
  },
  readMode: {
    type: Boolean,
    default: false,
  },
});

const hexCol = props.color
  ? props.color.startsWith('#')
    ? props.color
    : rgbToHex(props.color)
  : '#ffffff';

const action = ref('view');
const targetNote = ref(null);
const { getOwnUser } = useUsers();
const isOwnNote = (note) => {
  const ownUser = getOwnUser();
  return (
    ownUser && note.creating_user_id && note.creating_user_id === ownUser.id
  );
};
const { createNoteSchema, createNote, editNoteSchema, updateNote, deleteNote } =
  useNotes();
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
      sm: 'text-sm',
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
            'w-full flex items-center justify-between tracking-wider font-semibold ',
            resolveText({ size })
          )
        "
      >
        <div class="flex items-center gap-2">
          <span class="">
            <ChatBubbleLeftEllipsisIcon class="w-4 h-4 inline-block" />
            Note
          </span>
        </div>
        <!-- Buttons -->
        <div class="flex items-center gap-2">
          <button
            v-if="!readMode && isOwnNote(note)"
            :class="
              cn(
                action === 'edit' &&
                  targetNote.id === note.id &&
                  'text-destructive'
              )
            "
            @click="actions.edit(note)"
            title="Edit this note"
          >
            <PencilIcon class="w-4 h-4 hover:text-primary" />
          </button>
          <button
            v-if="!readMode && isOwnNote(note)"
            :class="
              cn(
                action === 'delete' &&
                  targetNote.id === note.id &&
                  'text-destructive'
              )
            "
            @click="actions.delete(note)"
            title="Delete this note"
          >
            <TrashIcon class="w-4 h-4 hover:text-destructive" />
          </button>
        </div>
      </div>

      <div class="flex justify-between items-center my-1">
        <span class="flex items-center gap-1">
          <span v-if="note.visibility === 0" title="Only I can see this note">
            <UserIcon class="w-4 h-4" />
          </span>
          <span
            v-if="note.visibility === 1"
            title="All team members can see this note"
          >
            <UsersIcon v-if="note.visibility === 1" class="w-4 h-4" />
          </span>
          <ProfileImage
            v-if="note.user"
            :src="note.user.profile_photo_url"
            :name="note.user.name"
            size="full"
            class="text-xs"
          >
            <template #before>by</template>
          </ProfileImage>
        </span>
        <span class="italic text-foreground/60 text-xs">
          {{ new Date(note.updated_at ?? note.created_at).toLocaleString() }}
        </span>
      </div>

      <div v-if="!readMode && action === 'edit' && targetNote.id === note.id">
        <AutoForm
          id="editNoteForm"
          :schema="schema"
          show-submit
          show-cancel
          :size="props.size"
          @submit="
            (data) =>
              onSubmit({
                data,
                fn: updateNote,
                message: 'Note successfully updated',
              })
          "
          @cancel="action = 'view'"
        />
      </div>
      <div v-else class="flex items-start gap-1 py-4 md:py-2 lg:py-0">
        <Bars3BottomLeftIcon class="w-4 h-4" />
        <p :class="cn('cursor-text', resolveText({ size }))">
          {{ note.content }}
        </p>
      </div>
      <div
        v-if="!readMode && action === 'delete' && targetNote.id === note.id"
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
          <Button variant="outline" :size="props.size" @click="action = 'view'"
            >Cancel</Button
          >
          <Button
            variant="destructive"
            :size="props.size"
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
    <li v-if="!readMode && action === 'add'">
      <AutoForm
        id="addNoteForm"
        :schema="schema"
        :size="props.size"
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
    <li v-if="!readMode && action === 'view'">
      <Button
        variant="outline-secondary"
        size="sm"
        class="w-full"
        @click.stop="actions.add()"
      >
        <PlusIcon class="w-4 h-4" /> Add Note
      </Button>
    </li>
  </ul>
</template>

<style scoped></style>
