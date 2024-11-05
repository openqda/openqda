<template>
    <div class="contents">
  <!-- editor toolbar -->
  <div
    v-show="false"
    class="block xl:flex lg:justify-center sticky top-0 py-2 z-40 bg-surface leading-10"
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
  <div class="absolute flex items-end bottom-10 right-16" style="z-index: 999">
    <span class="text-foreground/60 w-4 h-4 animate-spin" v-show="updating">
      <ArrowPathIcon class="w-4 h-4" />
    </span>
    <span
      id="selection-hash"
      class="w-6 h-6 text-center text-xs ArrowPathIcon border-0 bg-surface p-2 float-end"
      >0:0</span
    >
  </div>
  <CodingContextMenu @close="contextMenuClosed" />
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import Quill from 'quill';
import QuillCursors from 'quill-cursors';
import { formats, redoChange, undoChange } from '../../editor/EditorConfig.js';
import '../../editor/editor.css';
import { LineNumber } from '../../editor/LineNumber.js';
import { SelectionHighlightBG } from './editor/SelectionHighlightBG.js';
import { SelectionHash } from '../../editor/SelectionHash.js';
import EditorToolbar from '../../editor/EditorToolbar.vue';
import CodingContextMenu from './contextMenu/CodingContextMenu.vue';
import { Selections } from './selections/Selections.js';
import { flashMessage } from '../../Components/notification/flashMessage.js';
import { useCodes } from './useCodes.js';
import { useRange } from './useRange.js';
import { useSelections } from './selections/useSelections.js';
import { useCodingEditor } from './useCodingEditor.js';
import { useContextMenu } from './contextMenu/useContextMenu.js';
import { ArrowPathIcon } from '@heroicons/vue/20/solid';

let quillInstance;
const editorContent = ref('');
const contextMenu = useContextMenu();
const { selected, markToDelete } = useSelections();
const { observe, overlaps, selections, selectionsByIndex } = useCodes();
const { prevRange, setRange } = useRange();
const { setInstance, dispose } = useCodingEditor();
Quill.register('modules/lineNumber', LineNumber, true);
Quill.register('modules/selectionHash', SelectionHash, true);
Quill.register('modules/cursors', QuillCursors);
Quill.register('modules/highlight', SelectionHighlightBG);

const props = defineProps({
  project: Object,
  projectId: String,
  source: Object,
  codes: Array,
  locked: Boolean,
  CanUnlock: Boolean,
});
const projectId = props.project.id;
const disposables = new Set();

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
  quillInstance.on('selection-change', (data) => {
    const text = data ? quillInstance.getText(data) : '';
    setRange(data, text);
  });
  const hl = quillInstance.getModule('highlight');

  const disposeSelectionObserver = observe('store/selections', {
    // added: (selections) => {
    //   console.debug('Editor: add selections', selections);
    //   selections.forEach((selection) => {
    //     hl.highlight({
    //       id: selection.code.id,
    //       color: selection.code.color,
    //       title: selection.code.name,
    //       start: selection.start,
    //       length: selection.length,
    //       active: selection.code.active,
    //     });
    //   });
    // },
    // updated: (docs) => {
    //   console.debug('Editor: update selections', docs.length);
    //     docs.forEach((selection) => {
    //         hl.highlight({
    //             id: selection.code.id,
    //             color: selection.code.color,
    //             title: selection.code.name,
    //             start: selection.start,
    //             length: selection.length,
    //             active: selection.code.active,
    //         });
    //     });
    // },
    // removed: (docs) => {
    //   console.debug('Editor: remove selections', docs.length);
    //   docs.forEach(selection => hl.remove(selection))
    // }
  });
  disposables.add(disposeSelectionObserver);
  window.quill = quillInstance;

  watch(
    selections,
    (entries) => {
      updating.value = true;
      requestAnimationFrame(() => {
        entries.forEach((selection) => {
          hl.highlight({
            id: selection.code.id,
            color: selection.code.color,
            title: selection.code.name,
            start: selection.start,
            length: selection.length,
            active: selection.code.active,
          });
        });
        requestAnimationFrame(() => {
          updating.value = false;
        });
      });
    },
    { deep: true, immediate: true }
  );

  watch(overlaps, (entries) => {
    entries.forEach((entry) => hl.overlap(entry));
  });
});

const updating = ref(false);

onUnmounted(() => {
  if (quillInstance) {
    quillInstance = null;
    dispose();
  }
  disposables.forEach((fn) => fn());
});

watch(
  () => props.source,
  (newValue /*, oldValue*/) => {
    //quillInstance.setText(newValue)
    quillInstance.clipboard.dangerouslyPasteHTML(newValue.content);
  }
);

watch(selected, async ({ code }) => {
    // FIXME move this into composable!
  // skip if no usable selection was made
  if (!prevRange.value?.length || !code?.id) {
    return;
  }

  const { index, length } = prevRange.value;
  const start = index;
  const end = start + length;
  const text = quillInstance.getText(start, length);
  quillInstance.setSelection(null);

  // optimistic UI
  // we add the selection on the ui,
  // even if we don't know if it will work out
  // and remove it only in case it didn't work
  const selection = {
    id: 'unknown',
    start,
    end,
    length,
    text,
    code,
  };
  const editorEntry = {
    id: selection.code.id,
    color: selection.code.color,
    title: selection.code.name,
    start: selection.start,
    length: selection.length,
    active: selection.code.active,
  };
  const h = quillInstance.getModule('highlight');
  h.highlight(editorEntry);

  const { response, error } = await Selections.store({
    projectId: props.project.id,
    sourceId: props.source.id,
    code,
    start,
    end,
    text,
  });

  if (error || response.status >= 400) {
    const s = response.data.selection;
    selection.id = s.id; // update missing id
    flashMessage(error?.message ?? 'Failed to create code', { type: 'error' });
    h.remove(editorEntry);
  } else {
    Selections.by(projectId).add(selection);
    if (!code.text) {
      code.text = [];
    }
    code.text.push(selection);
  }
});

// TODO move to useContextMenu
const showContextMenu = (event) => {
  if (event.ctrlKey || event.metaKey) {
    // Allow the browser's context menu to appear
    return;
  }
  event.preventDefault();

  const selectedArea = quillInstance.getSelection();
  const hasSelection = selectedArea?.length;
  const linkedCodeId = event.target.getAttribute('data-code-id');
  const currentSelections = selectionsByIndex(
    selectedArea?.index,
    linkedCodeId
  );

  if (!hasSelection && !currentSelections.length) {
    return;
  }
  markToDelete(currentSelections);

  let lowest = selectedArea.index;
  let highest = selectedArea.index + selectedArea.length;

  if (currentSelections.length) {
    currentSelections.forEach((selection) => {
      if (selection.start < lowest) lowest = selection.start;
      if (selection.end > highest) highest = selection.end;
    });
  }

  const hm = quillInstance.getModule('highlight');
  hm.current({ index: lowest, length: highest - lowest });

  // use bounding rect to safely place the menu
    // const rect = quillInstance.getBounds(lowest, highest - lowest);

  contextMenu.open();
  const contextMenuElement = document.getElementById('contextMenu');

  const windowHeight = window.innerHeight;
  const windowWidth = window.innerWidth;

  let menuWidth = windowWidth / 4;
  if (menuWidth < 320) menuWidth = 320;

  let mouseX = event.clientX
  if (mouseX < menuWidth / 2) mouseX = menuWidth / 2

  let menuX = mouseX - (menuWidth / 2);
  const offsetX = windowWidth - (mouseX + menuWidth / 2)
  if (offsetX < 0) menuX += offsetX

  contextMenuElement.style.left = `${menuX}px`;
  contextMenuElement.style.width = `${menuWidth}px`;
  contextMenuElement.style.maxHeight = `${windowHeight / 3}px`;
  contextMenuElement.classList.remove('hidden');

  // Force a slight layout update so we can measure the contextMenu's dimensions
  contextMenuElement.offsetHeight;

  if (event.clientY + contextMenuElement.offsetHeight > windowHeight) {
    // If the context menu would go out of bounds, adjust its top position
    contextMenuElement.style.top = `${windowHeight - contextMenuElement.offsetHeight}px`;
  } else {
    contextMenuElement.style.top = `${event.clientY + 20}px`;
  }
};

const contextMenuClosed = () => {
  const hm = quillInstance.getModule('highlight');
  hm.current();
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
