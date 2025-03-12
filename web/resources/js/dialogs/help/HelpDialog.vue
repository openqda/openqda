<script setup lang="ts">
import Button from '../../Components/interactive/Button.vue';
import DialogBase from '../DialogBase.vue';
import { useHelpDialog } from './useHelpDialog';
import { ChevronRightIcon } from "@heroicons/vue/24/solid";

const Help = useHelpDialog();
const emit = defineEmits(['closed']);
defineProps({
  static: {
    type: Boolean,
    defaultValue: true,
  },
});

const close = () => {
  Help.close();
  emit('closed');
};
</script>

<template>
  <DialogBase
    title="Help, contact and feedback"
    :show="Help.isActive.value"
    @close="close"
  >
    <template #body>
        <div class="flex items-center my-3">
            <img :src="$page.props.logo" alt="Qdi logo" width="64" height="64" />
            <p class="text-sm">How can I help you?</p>
        </div>
        <ul class="text-sm space-y-3">
            <li class="flex">
                <button class="flex-grow hover:font-semibold">I need help</button>
                <ChevronRightIcon class="w-4 h-4 text-secondary" />
            </li>
        </ul>
      <slot name="info"></slot>
    </template>
    <template #footer>
      <div class="flex justify-between items-center w-full">
        <Button variant="outline" @click="close">Close</Button>
      </div>
    </template>
  </DialogBase>
</template>

<style scoped></style>
