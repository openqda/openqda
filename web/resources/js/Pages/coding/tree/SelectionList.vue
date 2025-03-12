<script setup lang="ts">
import { TrashIcon } from '@heroicons/vue/24/outline';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { useSelections } from '../selections/useSelections';
import { useCodingEditor } from '../useCodingEditor';
import ProfileImage from '../../../Components/user/ProfileImage.vue';
import {rgbToHex} from "../../../utils/color/toHex";

const props = defineProps({
  texts: Array,
  color: String
});

const hexCol = props.color.startsWith('#')
    ? props.color
    : rgbToHex(props.color)
const Selections = useSelections();
const { focusSelection } = useCodingEditor();
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
        <button
          class="p-2 me-1"
          @click="
            attemptAsync(
              () => Selections.deleteSelection(selection),
              'selection deleted'
            )
          "
          title="Delete this selection"
        >
          <TrashIcon class="w-4 h-4 hover:text-destructive" />
        </button>
      </div>
      <p class="cursor-text">{{ selection.text }}</p>
    </li>
  </ul>
</template>

<style scoped></style>
