<script setup lang="ts">
import {
  PencilIcon,
  TrashIcon,
  ChatBubbleLeftEllipsisIcon,
} from '@heroicons/vue/24/outline';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { useNotes } from '../../../domain/notes/useNotes';
import { useCodingEditor } from '../useCodingEditor';
import ProfileImage from '../../../Components/user/ProfileImage.vue';
import { rgbToHex } from '../../../utils/color/toHex';
import { ref } from 'vue';
import AutoForm from '../../../form/AutoForm.vue';

const props = defineProps({
  notes: Array,
  code: Object,
  color: String,
});

const editNote = ref(false);
const hexCol = props.color.startsWith('#')
  ? props.color
  : rgbToHex(props.color);
const { editNoteSchema, updateNote, deleteNote } = useNotes();
const { focusSelection } = useCodingEditor();
const schema = ref();
const onEditNote = (note) => {
  schema.value = editNoteSchema(note);
  editNote.value = true;
};
const onSubmit = async (data) => {
  await attemptAsync(() => updateNote(data, props.code), 'Note updated');
  editNote.value = false;
};
</script>

<template>
  <ul class="divide-y">
    <li
      v-for="note in props.notes"
      class="p-2 hover:bg-background/10"
      :key="note.id"
      :style="{ borderColor: hexCol }"
    >
      <div
        class="w-full flex items-center justify-between tracking-wider text-xs font-semibold"
      >
        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold">
            <ChatBubbleLeftEllipsisIcon class="w-3 h-3 inline-block" />
            Note
          </span>
          <span class="flex">
            <a
              v-if="note.scope"
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
            v-if="!editNote"
            class="p-2 me-1"
            @click="onEditNote(note)"
            title="Edit this note"
          >
            <PencilIcon class="w-4 h-4 hover:text-primary" />
          </button>
          <button
            class="p-2 me-1"
            @click="
              attemptAsync(() => deleteNote(note, props.code), 'Note deleted')
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
      <p v-else class="cursor-text overflow-x-scroll py-4 md:py-2 lg:py-0">
        {{ note.content }}
      </p>
    </li>
  </ul>
</template>

<style scoped></style>
