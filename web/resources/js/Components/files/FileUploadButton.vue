<template>
  <label>
    <input
      type="file"
      ref="fileInput"
      :multiple="props.multiple ? true : undefined"
      @change="onFileInputChanged"
      :accept="`${accept ?? '*'}`"
      class="hidden"
    />
    <slot />
  </label>
</template>

<script setup>
import { ref } from 'vue';

const emit = defineEmits(['fileAdded']);
const fileInput = ref(null);
const props = defineProps([
  'multiple',
  'title',
  'accept',
  'label',
  'color',
  'fileSizeLimit',
  'icon',
]);

function onFileInputChanged() {
  const files = fileInput.value?.files ?? [];
  const copy = [...files];
  // TODO check file size based on .env config and filetype
  emit('fileAdded', copy);
}
</script>
