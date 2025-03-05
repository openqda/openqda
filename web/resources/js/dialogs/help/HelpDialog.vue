<script setup lang="ts">
import Button from '../../Components/interactive/Button.vue';
import DialogBase from '../DialogBase.vue';
import { useHelpDialog } from './useHelpDialog';

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
        <div class="flex items-center">
            <img :src="$page.props.logo" alt="Qdi logo" width="64" height="64" />
            <p>How can I help you?</p>
        </div>
        <div class="flex items-center text-xs space-x-1">
            <Button size="sm" variant="default">Help</Button>
            <Button size="sm" variant="default">Provide feedback</Button>
            <Button size="sm" variant="default">Report an issue</Button>
        </div>
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
