<script setup lang="ts">
import Button from '../Components/interactive/Button.vue';
import DialogBase from './DialogBase.vue';
import { ref, watch } from 'vue';

const emit = defineEmits(['confirmed', 'cancelled']);
const open = ref(false);

const props = defineProps({
  title: String,
  text: String,
  showConfirm: Boolean,
  cancelButtonLabel: String,
  confirmButtonLabel: String,
  static: {
    type: Boolean,
    defaultValue: true,
  },
});

watch(
  () => props.text,
  () => start()
);

const start = () => {
  open.value = true;
};

const confirm = () => {
  open.value = false;
  emit('confirmed');
};

const cancel = () => {
  open.value = false;
  emit('cancelled');
};
</script>

<template>
  <DialogBase :title="props.title ?? 'Confirm decision'" :show="open" @close="cancel">
    <template #body>
      <p>{{ props.text }}</p>
      <slot name="info"></slot>
    </template>
    <template #footer>
      <div class="flex justify-between items-center w-full">
        <Button variant="outline" @click="cancel"
          >{{ props.cancelButtonLabel ?? 'Cancel' }}
        </Button>
        <Button v-if="props.showConfirm" @click="confirm"
          >{{ props.confirmButtonLabel ?? 'Confirm' }}
        </Button>
      </div>
    </template>
  </DialogBase>
</template>

<style scoped></style>
