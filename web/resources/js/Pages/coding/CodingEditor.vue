<template>
  <div class="contents">
    <!-- editor toolbar -->
    <Headline1 class="px-3 py-6">{{ props.source?.name }}</Headline1>
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
    <div :class="cn('flex', loadingDocument && 'hidden')">
      <div id="lineNumber"></div>
      <div
        id="editor"
        class="flex-grow"
        @contextmenu="showContextMenu"
        @dragenter.prevent
        @dragover.prevent
        @drop.prevent
      ></div>
    </div>
    <div
      class="absolute flex items-end bottom-10 right-16"
      style="z-index: 999"
    >
      <span class="text-foreground/60 w-4 h-4 animate-spin" v-show="updating">
        <ArrowPathIcon class="w-4 h-4" />
      </span>
      <span
        id="selection-hash"
        class="w-6 h-6 text-center text-xs ArrowPathIcon border-0 bg-surface p-2 float-end"
        >0:0</span
      >
    </div>
    <div v-if="loadingDocument" class="p-3">
      <ActivityIndicator>{{ 'Loading source' }}</ActivityIndicator>
    </div>
    <CodingContextMenu @close="contextMenuClosed" />
  </div>
</template>

<script setup>
import { nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import Quill from 'quill';
import QuillCursors from 'quill-cursors';
import { formats, redoChange, undoChange } from '../../editor/EditorConfig.js';
import '../../editor/editor.css';
import { LineNumber } from '../../editor/LineNumber.js';
import { SelectionHighlightBG } from './editor/SelectionHighlightBG.js';
import { SelectionHash } from '../../editor/SelectionHash.js';
import EditorToolbar from '../../editor/EditorToolbar.vue';
import CodingContextMenu from './contextMenu/CodingContextMenu.vue';
import { flashMessage } from '../../Components/notification/flashMessage.js';
import { useCodes } from './useCodes.js';
import { useRange } from './useRange.js';
import { useSelections } from './selections/useSelections.js';
import { useCodingEditor } from './useCodingEditor.js';
import { useContextMenu } from './contextMenu/useContextMenu.js';
import { ArrowPathIcon } from '@heroicons/vue/20/solid';
import Headline1 from '../../Components/layout/Headline1.vue';
import { asyncTimeout } from '../../utils/asyncTimeout.js';
import ActivityIndicator from '../../Components/ActivityIndicator.vue';
import { cn } from '../../utils/css/cn.js';

const editorContent = ref('');
const contextMenu = useContextMenu();
const { selected, createSelection, markToDelete } = useSelections();
const { observe, selections, selectionsByIndex } = useCodes();
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
  sources: Array,
  codes: Array,
  locked: Boolean,
  CanUnlock: Boolean,
});

const disposables = new Set();

let quillInstance;
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

  window.quill = quillInstance;

  const disposeSelectionObserver = observe('store/selections', {
    added: (docs) => {
      updating.value = true;
      nextTick(() => {
        const related = new Set(docs);
        docs.forEach((doc) => {
          related.add(...selectionsByIndex(doc.start));
          related.add(...selectionsByIndex(doc.end));
        });
        hl.add([...related]);
        updating.value = false;
      });
    },
    updated: (docs /*, allDocs */) => {
      hl.add(docs);
    },
    removed: (docs) => {
      docs.forEach((doc) => hl.remove(doc));
      hl.add(
        selections.value.filter((sel) => {
          return !docs.some((doc) => doc.id === sel.id);
        })
      );
    },
  });

  disposables.add(disposeSelectionObserver);

  const addSelections = (entries) => {
    // entries.forEach((selection) => {
    //   hl.highlight({
    //     id: selection.code.id,
    //     color: selection.code.color,
    //     title: selection.code.name,
    //     start: selection.start,
    //     length: selection.length,
    //     active: selection.code.active,
    //   });
    // });

    hl.add(entries);
    updating.value = false;
  };

  let initialWatcher;
  initialWatcher = watch(
    selections,
    (entries) => {
      if (entries?.length) {
        setTimeout(() => {
          addSelections(entries);
          if (typeof initialWatcher !== 'undefined') {
            initialWatcher.stop();
          }
        }, 300);
      }
    },
    { deep: false, immediate: true }
  );

  loadingDocument.value = false;
});

watch(
  () => props.source,
  async (newValue /*, oldValue*/) => {
    //quillInstance.setText(newValue)
    loadingDocument.value = true;
    await asyncTimeout(300);
    quillInstance.clipboard.dangerouslyPasteHTML(newValue.content);
  }
);

const loadingDocument = ref(true);
const updating = ref(false);

onUnmounted(() => {
  if (quillInstance) {
    quillInstance = null;
    dispose();
  }
  disposables.forEach((fn) => fn());
});

watch(selected, async ({ code }) => {
  // FIXME move this into composable!
  // skip if no usable selection was made
  if (!prevRange.value?.length || !code?.id) {
    return;
  }

  const { index, length } = prevRange.value;
  const text = quillInstance.getText(index, length);
  quillInstance.setSelection(null);

  const selection = await createSelection({
    code,
    index,
    length,
    text,
  });

  if (!selection) {
    flashMessage(
      `Failed to create selection at <${index}:${index + length}> for code ${code?.name}`,
      { type: 'error' }
    );
    // TODO: remove selection from editor?
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

  let mouseX = event.clientX;
  if (mouseX < menuWidth / 2) mouseX = menuWidth / 2;

  let menuX = mouseX - menuWidth / 2;
  const offsetX = windowWidth - (mouseX + menuWidth / 2);
  if (offsetX < 0) menuX += offsetX;

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
  if (!quillInstance) return;
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
