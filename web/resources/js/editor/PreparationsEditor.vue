<template>
  <!-- editor toolbar -->
  <div
    class="block xl:flex lg:justify-center sticky top-0 py-2 z-40 bg-surface mx-0 md:mx-2"
  >
    <div
      id="toolbar"
      class="rounded-none mb-3 xl:mb-0 lg:rounded-full border-2 bg-surface z-150 shadow-lg border-foreground/20 py-2 px-4 inline-flex text-foreground/60! text-center"
    >
      <EditorToolbar />
    </div>
    <slot name="actions"></slot>
  </div>
  <!-- editor content -->
  <div :class="cn('flex', loadingDocument && 'hidden')">
    <div id="lineNumber"></div>
    <div id="editor" class="grow"></div>
  </div>
  <div
    class="fixed bottom-4 right-4 grow flex items-end"
    style="z-index: 50"
  >
    <slot name="status"></slot>
    <span
      v-if="contentHash?.hash"
      class="text-xs border-0 bg-surface p-1 font-mono me-3"
      :title="contentHash.hash"
      >Integrity: {{ contentHash.short }}</span
    >
    <span
      id="selection-hash"
      class="text-center text-xs ArrowPathIcon border-0 bg-surface p-1 font-mono"
      >0:0</span
    >
  </div>
  <div v-if="loadingDocument" class="p-3 block w-full">
    <ActivityIndicator class="w-full">{{
      'Loading Document'
    }}</ActivityIndicator>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import Quill from 'quill';
import QuillCursors from 'quill-cursors';
import EditorToolbar from './EditorToolbar.vue';
import { LineNumber } from './LineNumber.js';
import { SelectionHash } from './SelectionHash.js';
import { formats, redoChange, undoChange } from './EditorConfig.js';
import { debounce } from '../utils/dom/debounce.js';
import './editor.css';
import { retry } from '../utils/dom/retry.js';
import { asyncTimeout } from '../utils/asyncTimeout.js';
import { cn } from '../utils/css/cn.js';
import ActivityIndicator from '../Components/ActivityIndicator.vue';
import { createHash } from '../utils/createHash.js';
import { SelectionHighlightBG } from '../Pages/coding/editor/SelectionHighlightBG.js';

const props = defineProps({
  source: String,
  locked: Boolean,
  CanUnlock: Boolean,
});

const loadingDocument = ref(false);

let quillInstance;
const Delta = Quill.import('delta');
const editorContent = ref('');
const unsaved = ref(false);
const contentHash = ref('');

Quill.register('modules/lineNumber', LineNumber, true);
Quill.register('modules/selectionHash', SelectionHash, true);
Quill.register('modules/cursors', QuillCursors);
Quill.register('modules/highlight', SelectionHighlightBG);

const emit = defineEmits(['status', 'autosave', 'settings']);
onMounted(() => {
  quillInstance = new Quill('#editor', {
    theme: 'snow',
    formats: formats.concat(['id', 'title', 'class']),
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
          settings: () => emit('settings'),
        },
      },
      lineNumber: {
        container: '#lineNumber',
        textChange: { debounce: 500 },
        resize: { debounce: 250 },
      },
      selectionHash: {
        container: '#selection-hash',
      },
    },
  });

  let change = new Delta();
  const runAutosave = debounce(() => {
    if (change.length() > 0) {
      emit('autosave', quillInstance.getSemanticHTML());
      change = new Delta();
      unsaved.value = false;
      emit('status', { value: 'saved' });
    }
  }, 1000);

  quillInstance.on('text-change', function (delta, oldDelta, source) {
    if (source === 'user') {
      change = change.compose(delta);
      runAutosave();

      if (!unsaved.value) {
        unsaved.value = true;
        emit('status', { value: 'unsaved' });
      }
    }
  });

  quillInstance.on('editor-change', function (/* eventName, ...args */) {
    editorContent.value = quillInstance.root.innerHTML;
  });

  quillInstance.on(
    'text-change',
    debounce(async () => {
      const data = quillInstance.getContents();
      const hash = (await createHash(data)) ?? '';
      const short = hash.substring(0, 8);
      contentHash.value = {
        hash,
        short,
      };
    }, 500)
  );

  quillInstance.enable(!props.locked);
  const ln = quillInstance.getModule('lineNumber');
  clearLinesRepeat = retry(() => ln.update(), 10);

  // Check for unsaved data
  window.onbeforeunload = function () {
    if (change.length() > 0) {
      return 'There are unsaved changes. Are you sure you want to leave?';
    }
  };

  watch(
    () => props.source,
    async (newValue /*, oldValue*/) => {
      loadingDocument.value = true;
      quillInstance.enabled = false;
      await asyncTimeout(300);
      //quillInstance.setText(newValue)
      quillInstance.clipboard.dangerouslyPasteHTML(newValue);
      loadingDocument.value = false;
      quillInstance.enabled = true;
    }
  );
});

let clearLinesRepeat;

onUnmounted(() => {
  clearLinesRepeat();

  if (quillInstance) {
    const lineNumberModule = quillInstance.getModule('lineNumber');
    lineNumberModule.dispose();
    quillInstance = null;
  }
  window.onbeforeunload = null;
});

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
.ql-clipboard {
  white-space: pre-wrap !important;
}
.ql-container.ql-snow {
  line-height: 18.4667;
  border: none !important;
}

#lineNumber {
  text-align: end;
  font-size: 10px;
  font-family: 'Lucida Console', monospace, sans-serif;
  padding: 12px 5px;
  line-height: 18.4667;
  vertical-align: top;
  box-sizing: border-box;
}
</style>
