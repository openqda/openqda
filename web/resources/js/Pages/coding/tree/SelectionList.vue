<script setup lang="ts">
import {
  TrashIcon,
  ChatBubbleLeftEllipsisIcon,
} from '@heroicons/vue/24/outline';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { useSelections } from '../selections/useSelections';
import { useCodingEditor } from '../useCodingEditor';
import ProfileImage from '../../../Components/user/ProfileImage.vue';
import { rgbToHex } from '../../../utils/color/toHex';
import NoteList from './NoteList.vue';
import Button from '../../../Components/interactive/Button.vue';
import { ref } from 'vue';
import { cn } from '../../../utils/css/cn';

const props = defineProps({
  texts: Array,
  color: String,
  code: Object,
  readMode: {
    type: Boolean,
    default: false,
  },
});

const hexCol = props.color.startsWith('#')
  ? props.color
  : rgbToHex(props.color);
const { deleteSelection } = useSelections();
const { focusSelection } = useCodingEditor();

//------------------------------------------------------------------------
// MANAGE NOTES
//------------------------------------------------------------------------
const manageNotesTarget = ref(null);
const startManageNotes = (selection) => {
  deleteTarget.value = null;
  manageNotesTarget.value =
    selection === manageNotesTarget.value ? null : selection;
};

//------------------------------------------------------------------------
// DELETE SELECTIONS
//------------------------------------------------------------------------
const deleteTarget = ref(null);
const startDelete = (selection) => {
  manageNotesTarget.value = null;
  deleteTarget.value = selection === deleteTarget.value ? null : selection;
};
const onDeleteSelection = async () => {
  await attemptAsync(async () => {
    const data = { ...deleteTarget.value };
    data.code = props.code;
    const result = await deleteSelection(data);
    if (!result) {
      throw new Error('Failed to delete selection');
    }
  }, 'Selection successfully deleted');
  deleteTarget.value = null;
};
</script>

<template>
  <ul class="divide-y">
    <li
      v-for="selection in props.texts"
      class="p-3 hover:bg-background/20"
      :key="selection.id"
      :style="{ borderColor: hexCol }"
    >
      <div
        class="w-full flex items-center justify-between tracking-wider text-xs font-semibold"
      >
        <span class="flex">
          <a
            href=""
            @click.prevent="focusSelection(selection)"
            class="font-mono hover:underline"
            >{{ selection.start }}:{{ selection.end }}</a
          >
          <ProfileImage
            v-if="selection.user"
            :src="selection.user.profile_photo_url"
            :name="selection.user.name"
            class="w-3 h-3 ms-1"
          />
        </span>
        <span class="flex items-center gap-2">
          <button
            v-if="!readMode"
            class="flex items-center"
            :class="
              cn(
                selection === manageNotesTarget
                  ? 'text-primary'
                  : 'hover:text-primary'
              )
            "
            @click="startManageNotes(selection)"
            title="Manage notes for this selection"
          >
            <ChatBubbleLeftEllipsisIcon class="w-4 h-4" />
            <span>{{ selection.notes?.length ?? 0 }}</span>
          </button>
          <button
            v-if="!readMode"
            :class="
              cn(
                selection === deleteTarget
                  ? 'text-destructive'
                  : 'hover:text-destructive'
              )
            "
            @click="startDelete(selection)"
            title="Delete this selection"
          >
            <TrashIcon class="w-4 h-4" />
          </button>
        </span>
      </div>
      <p class="cursor-text overflow-x-scroll py-4 md:py-2 lg:py-0">
        {{ selection.text }}
      </p>
      <div
        v-if="!readMode && deleteTarget === selection"
        class="mt-2 p-3 border border-destructive rounded-md text-sm"
      >
        <div class="font-semibold text-destructive">Delete this selection</div>
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
        v-if="!readMode && manageNotesTarget === selection"
        class="mt-2 border-t border-dashed py-2"
        :style="{ borderColor: hexCol }"
      >
        <div class="font-semibold">Manage notes this selection</div>
        <NoteList
          :notes="selection.notes"
          type="selection"
          :target="selection"
          :read-mode="props.readMode"
        />
      </div>
    </li>
  </ul>
</template>

<style scoped></style>
