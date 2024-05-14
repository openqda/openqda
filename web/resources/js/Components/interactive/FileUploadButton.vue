<template>
  <label>
    <input
      type="file"
      ref="fileInput"
      @change="onFileInputChanged"
      :accept="`${accept ?? '*'}`"
      class="hidden"
    />
    <span
      :class="`space-x-2 rounded bg-${color}-700 px-2 py-1 text-xs font-semibold text-white shadow-sm hover:bg-${color}-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-${color}-500 inline-flex items-center`"
    >
      <component :is="icon" class="h-4 w-4 text-white"></component>
      <span>{{ label }}</span>
    </span>
  </label>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['fileAdded'])
const fileInput = ref(null)
const props = defineProps([
  'multiple',
  'title',
  'accept',
  'label',
  'color',
  'fileSizeLimit',
  'icon',
])

function onFileInputChanged() {
  const files = fileInput.value?.files

  for (let i = 0; i < files.length; i++) {
    const file = files[i]
    const maxFileSize = props.fileSizeLimit * 1024 * 1024 // 30MB in bytes

    if (file.size > maxFileSize) {
      alert(`File exceeds maximum size (${props.fileSizeLimit} MB)`)
      return // Prevent further processing
    }
  }
  emit('fileAdded', { files: fileInput.value?.files })
}
</script>
