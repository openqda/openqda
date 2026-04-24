<script setup lang="ts">
import {
  ChatBubbleLeftEllipsisIcon,
  PencilIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import { attemptAsync } from '../../Components/notification/attemptAsync';
import AutoForm from '../../form/AutoForm.vue';
import ProfileImage from '../../Components/user/ProfileImage.vue';
import { useNotes } from './useNotes';
import { useCodingEditor } from '../../Pages/coding/useCodingEditor';
import { ref } from 'vue';

const { focusSelection } = useCodingEditor();
const { deleteNote, updateNote, editNoteSchema } = useNotes();
const schema = ref();
const editNote = ref(false);

const onEditNote = (note) => {
  schema.value = editNoteSchema(note);
  editNote.value = true;
};
const onSubmit = async (data) => {
  await attemptAsync(() => updateNote(data, props.target), 'Note updated');
  editNote.value = false;
};

const props = defineProps({
  note: Object,
  target: Object,
});
</script>

<template>
  <div class="w-full">
    <div
      class="flex items-center justify-between tracking-wider text-xs font-semibold"
    >
      <div class="flex items-center gap-2">
        <span class="text-xs font-semibold">
          <ChatBubbleLeftEllipsisIcon class="w-3 h-3 inline-block" />
          Note
        </span>
        <span class="flex">
          <a
            v-if="
              note.scope?.start !== undefined && note.scope?.end !== undefined
            "
            href="#"
            @click.prevent="focusSelection(note.scope)"
            class="font-mono hover:underline"
            >scope: {{ note.scope.start }}:{{ note.scope.end }}</a
          >
        </span>
      </div>
      <!-- Buttons -->
      <div class="flex items-center gap-2">
        <ProfileImage
          v-if="note.user"
          :src="note.user.profile_photo_url"
          :name="note.user.name"
          class="w-3 h-3"
        />
        <button
          v-if="!editNote"
          @click.stop="onEditNote(note)"
          title="Edit this note"
        >
          <PencilIcon class="w-4 h-4 hover:text-primary" />
        </button>
        <button
          @click.prevent="
            attemptAsync(() => deleteNote(note, props.target), 'Note deleted')
          "
          title="Delete this note"
        >
          <TrashIcon class="w-4 h-4 hover:text-destructive" />
        </button>
      </div>
    </div>

    <div v-if="editNote">
      <AutoForm
        id="editNoteForm"
        :schema="schema"
        show-submit
        show-cancel
        @close="editNote = false"
        @submit="onSubmit"
        @cancel="editNote = false"
      />
    </div>
    <p
      v-else
      class="cursor-text overflow-x-scroll py-4 md:py-2 lg:py-0 px-0.5 text-sm"
    >
      {{ note.content }}
    </p>
  </div>
</template>

<style scoped></style>
