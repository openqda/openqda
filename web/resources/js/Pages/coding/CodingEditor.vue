<template>
  <!-- editor toolbar -->
  <div
    v-show="false"
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
  <CodingContextMenu :codes="activeCodes" @code-deleted="selection => quillInstance.getModule('highlight').highlight(selection)" />
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import Quill from 'quill';
import QuillCursors from 'quill-cursors';
import { formats, redoChange, undoChange } from '../../editor/EditorConfig.js';
import '../../editor/editor.css';
import { LineNumber } from '../../editor/LineNumber.js';
import { SelectionHighlightBG } from './editor/SelectionHighlightBG.js';
import { SelectionHash } from '../../editor/SelectionHash.js';
import EditorToolbar from '../../editor/EditorToolbar.vue';
import CodingContextMenu from './contextMenu/CodingContextMenu.vue';
import { Selections } from './Selections.js';
import { flashMessage } from '../../Components/notification/flashMessage.js';
import { useCodes } from './useCodes.js';
import { useRange } from './useRange.js';
import { useSelections } from './selections/useSelections.js';
import { useCodingEditor } from './useCodingEditor.js';
import { useContextMenu } from './contextMenu/useContextMenu.js'

let quillInstance;
const Delta = Quill.import('delta');
const editorContent = ref('');
const contextMenu = useContextMenu()
const { selected, select, markToDelete, markCurrentByCodeId } = useSelections();
const { codes, selections, codesInRange } = useCodes();
const { prevRange, setRange } = useRange();
const activeCodes = computed(() => codes.value.filter((c) => c.active));
const { setInstance, dispose } = useCodingEditor();
Quill.register('modules/lineNumber', LineNumber, true);
Quill.register('modules/selectionHash', SelectionHash, true);
Quill.register('modules/cursors', QuillCursors);
Quill.register('modules/highlight', SelectionHighlightBG);

const emit = defineEmits(['code-assigned']);
const props = defineProps({
  project: Object,
  projectId: String,
  source: Object,
  codes: Array,
  locked: Boolean,
  CanUnlock: Boolean,
});
const projectId = props.project.id;
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

  // make available in other templates
  setInstance(quillInstance);

  quillInstance.enable(false);
  quillInstance.clipboard.dangerouslyPasteHTML(props.source.content);

  /*
   * Update selected range to shared state
   */
  quillInstance.on('selection-change', setRange);
  const highlight = quillInstance.getModule('highlight');

  watch(
    selections,
    (entries) => {
        console.debug('recompute entries', entries)
      entries.forEach((selection) =>
        highlight.highlight({
          id: selection.code.id,
          color: selection.code.color,
          start: selection.start,
          length: selection.length,
          active: selection.code.active,
        })
      );
    },
    { deep: true, immediate: true }
  );
});

onUnmounted(() => {
  if (quillInstance) {
    quillInstance = null;
    dispose();
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
  if (!prevRange.value?.length || !code?.id) {
    return;
  }

  const { index, length } = prevRange.value;
  const start = index;
  const end = start + length;
  const text = quillInstance.getText(start, length);
  const { error } = await Selections.store({
    projectId: props.project.id,
    sourceId: props.source.id,
    code,
    start,
    end,
    text,
  });
  // flash message if error
  if (error) {
    flashMessage(error.message, { type: 'error' });
  } else {
    quillInstance.setSelection(null);

  }
});

// TODO move to useContextMenu
const showContextMenu = (event) => {
  if (event.ctrlKey || event.metaKey) {
    // Allow the browser's context menu to appear
    return;
  }
  event.preventDefault();

  const selectedCode = quillInstance.getSelection();
  const hasSelection = selectedCode?.length;
  const linkedCodeId = event.target.getAttribute('data-code-id');
  const { codes, selection } = codesInRange(selectedCode?.index, linkedCodeId);
  if (!hasSelection && !codes.length) {
    return;
  }
  markCurrentByCodeId(selection);
  markToDelete(codes);

  contextMenu.open();
  const contextMenuElement = document.getElementById('contextMenu');
  const windowHeight = window.innerHeight;

  contextMenuElement.style.left = `${event.clientX + 100}px`;
  contextMenuElement.classList.remove('hidden');

  // Force a slight layout update so we can measure the contextMenu's dimensions
  contextMenuElement.offsetHeight;

  if (event.clientY + contextMenuElement.offsetHeight > windowHeight) {
    // If the context menu would go out of bounds, adjust its top position
    contextMenuElement.style.top = `${windowHeight - contextMenuElement.offsetHeight}px`;
  } else {
    contextMenuElement.style.top = `${event.clientY}px`;
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
