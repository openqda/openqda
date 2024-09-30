<template>
  <div class="flex justify-center sticky top-3 z-40">
      <slot name="status"></slot>
    <div
      id="toolbar"
      class="rounded-full border-2 bg-surface z-150 shadow-lg border-foreground/20 py-2 px-4 inline-flex !text-foreground/60"
    >
      <EditorToolbar />
    </div>
    <slot name="actions"></slot>
  </div>
  <div id="editor"></div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import Quill from 'quill';
import QuillCursors from 'quill-cursors';
import EditorToolbar from '../../editor/EditorToolbar.vue';
import { formats, redoChange, undoChange } from '../../editor/EditorConfig.js';
import { debounce } from '../../utils/dom/debounce.js'
import '../../editor/editor.css';

let quillInstance;
const Delta = Quill.import('delta');
const editorContent = ref('');
const unsaved = ref(false)

Quill.register('modules/cursors', QuillCursors);
const emit = defineEmits(['status', 'autosave']);

onMounted(() => {
  quillInstance = new Quill('#editor', {
    theme: 'snow',
    formats,
    placeholder: 'Start writing or paste content...',
    modules: {
      syntax: false,
      history: {
        delay: 2000,
        maxStack: 500,
        userOnly: true,
      },
      toolbar: {
        container: '#toolbar',
        handlers: {
          undo: undoChange,
          redo: redoChange,
        },
      },
    },
  });

  let change = new Delta();
  const runAutosave = debounce(() => {
        if (change.length() > 0) {
            emit('autosave')
            change = new Delta();
            unsaved.value = false
            emit('status', { value: 'saved' })
        }
    }, 1000)

  quillInstance.on('text-change', function (delta, oldDelta, source) {
    if (source === 'user') {
      change = change.compose(delta);
      runAutosave()

      if (!unsaved.value) {
          unsaved.value = true
          emit('status', { value: 'unsaved' })
      }
    }
  });

  quillInstance.on('editor-change', function (/* eventName, ...args */) {
    editorContent.value = quillInstance.root.innerHTML;
  });

  quillInstance.enable(!props.locked);


  // Check for unsaved data
  window.onbeforeunload = function () {
    if (change.length() > 0) {
      return 'There are unsaved changes. Are you sure you want to leave?';
    }
  };
});

let autoSaveTimer;

onUnmounted(() => {
  if (quillInstance) {
    quillInstance = null;
  }
});

const props = defineProps({
  source: String,
  locked: Boolean,
  CanUnlock: Boolean,
});

watch(
  () => props.source,
  (newValue /*, oldValue*/) => {
    //quillInstance.setText(newValue)
    quillInstance.clipboard.dangerouslyPasteHTML(newValue);
  }
);

watch(
  () => props.locked,
  (newValue /*, oldValue*/) => {
    //quillInstance.setText(newValue)

    quillInstance.enable(!newValue);
  }
);

defineExpose({ editorContent });
</script>

<style scoped>
.ql-container.ql-snow {
  border: none !important;
}
</style>
