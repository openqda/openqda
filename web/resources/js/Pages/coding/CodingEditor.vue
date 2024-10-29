<template>
  <!-- editor toolbar -->
  <div
    class="block xl:flex lg:justify-center sticky top-0 py-2 z-40 bg-surface"
  >
    <div
      id="toolbar"
      class="rounded-none mb-3 xl:mb-0 lg:rounded-full border-2 bg-surface z-150 shadow-lg border-foreground/20 py-2 px-4 inline-flex !text-foreground/60"
    >
      <EditorToolbar />
    </div>
    <slot name="actions"></slot>
  </div>
  <!-- editor content -->
  <div class="flex">
    <div id="lineNumber"></div>
    <div
      id="editor"
      class="flex-grow"
      @contextmenu="showContextMenu"
      @dragenter.prevent
      @dragover.prevent
      @drop.prevent="console.debug"
    ></div>
  </div>
  <div class="absolute bottom-10 right-10" style="z-index: 999">
    <slot name="status"></slot>
    <span
      id="selection-hash"
      class="w-6 h-6 text-center text-xs text-foreground/60 border-0 bg-surface p-2"
      >0:0</span
    >
  </div>
  <CodingContextMenu
    @close="contextMenuVisible = false"
    :codes="$props.codes"
    :visible="contextMenuVisible"
  />
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import Quill from 'quill';
import QuillCursors from 'quill-cursors';
import { formats, redoChange, undoChange } from '../../editor/EditorConfig.js';
import '../../editor/editor.css';
import { LineNumber } from '../../editor/LineNumber.js';
import { SelectionHighlightBG } from './editor/SelectionHighlightBG.js'
import { SelectionHash } from '../../editor/SelectionHash.js';
import EditorToolbar from '../../editor/EditorToolbar.vue';
import CodingContextMenu from './contextMenu/CodingContextMenu.vue';
import { useContextMenu } from './contextMenu/useContextMenu.js';
import { Selections } from './Selections.js';
import { flashMessage } from '../../Components/notification/flashMessage.js';

let quillInstance;
const Delta = Quill.import('delta');
const editorContent = ref('');
const contextMenuVisible = ref(false);
const lastRange = ref();
const { selected, markToDelete } = useContextMenu();

Quill.register('modules/lineNumber', LineNumber, true);
Quill.register('modules/selectionHash', SelectionHash, true);
Quill.register('modules/cursors', QuillCursors);
Quill.register('modules/highlight', SelectionHighlightBG);

const emit = defineEmits(['code-assigned']);
const props = defineProps({
  project: Object,
  source: Object,
  codes: Array,
  locked: Boolean,
  CanUnlock: Boolean,
});

onMounted(() => {
  quillInstance = new Quill('#editor', {
    theme: 'snow',
    formats: formats.concat(['id']),
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
      lineNumber: {
        container: '#lineNumber',
      },
      selectionHash: {
        container: '#selection-hash',
      },
      highlight: {},
    },
  });

  quillInstance.enable(false);
  quillInstance.clipboard.dangerouslyPasteHTML(props.source.content);
  quillInstance.on('selection-change', (data) => {
    if (data !== null) {
      lastRange.value = data;
    }
  });
  const highlight = quillInstance.getModule('highlight')
  props.codes.forEach(code => {
      if (code.text.length) {
          code.text.forEach(({ start, end }) => {
              highlight.highlight({
                  id: code.id,
                  start: Number(start),
                  end: Number(end),
                  color: code.color
              })
          })
      }
  })
});

onUnmounted(() => {
  if (quillInstance) {
    quillInstance = null;
  }
});

watch(
  () => props.source,
  (newValue /*, oldValue*/) => {
    //quillInstance.setText(newValue)
    quillInstance.clipboard.dangerouslyPasteHTML(newValue.content);
  }
);

watch(selected, async ({ code, parent }) => {
  // skip if no usable selection was made
  if (!lastRange.value?.length || !code?.id) {
    return;
  }

  const { index, length } = lastRange.value;
  const start = index;
  const end = start + length;
  const text = quillInstance.getText(start, length);
  const { error } = await Selections.store({
    projectId: props.project.id,
    sourceId: props.source.id,
    codeId: code.id,
    start,
    end,
    text,
  });
  // flash message if error
  if (error) {
    flashMessage(error.message, { type: 'error' });
  }
});

const showContextMenu = (event) => {
  if (event.ctrlKey || event.metaKey) {
    // Allow the browser's context menu to appear
    return;
  }
  event.preventDefault();
  const selectedCode = quillInstance.getSelection()
  const codeId = event.target.getAttribute('data-code-id') ?? null;
  const codes = props.codes.filter(code => code.id === codeId);
  markToDelete(codes)

  contextMenuVisible.value = true;
  const contextMenu = document.getElementById('contextMenu');
  const windowHeight = window.innerHeight;

  contextMenu.style.left = `${event.clientX}px`;
  contextMenu.classList.remove('hidden');

  // Force a slight layout update so we can measure the contextMenu's dimensions
  contextMenu.offsetHeight;

  if (event.clientY + contextMenu.offsetHeight > windowHeight) {
    // If the context menu would go out of bounds, adjust its top position
    contextMenu.style.top = `${windowHeight - contextMenu.offsetHeight}px`;
  } else {
    contextMenu.style.top = `${event.clientY}px`;
  }
};

defineExpose({ editorContent });
</script>

<style scoped>
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
