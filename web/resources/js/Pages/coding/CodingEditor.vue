<template>
  <div class="contents">
    <!-- editor header with zoom controls -->
    <div class="px-3 py-6 block md:flex items-center justify-between">
      <Headline1 class="hidden md:block m-0">{{
        props.source?.name
      }}</Headline1>
      <div class="flex gap-1 justify-end items-center">
        <Transition name="fade">
          <Button
            v-if="range?.length"
            variant="outline-secondary"
            @click="showContextMenu"
            class="hidden"
            >Menu</Button
          >
        </Transition>
        <Button
          variant="outline"
          class="p-1 md:p-2 rounded hover:bg-foreground/10 transition-colors"
          title="Zoom Out"
          @click="setZoom('decrease')"
        >
          <svg
            viewBox="0 0 18 18"
            class="w-5 h-5 stroke-current fill-none stroke-1"
          >
            <line x1="6" x2="12" y1="9" y2="9"></line>
            <circle cx="9" cy="9" r="6"></circle>
          </svg>
        </Button>
        <Button
          variant="outline"
          class="p-1 md:p-2 rounded hover:bg-foreground/10 transition-colors"
          title="Zoom In"
          @click="setZoom('increase')"
        >
          <svg
            viewBox="0 0 18 18"
            class="w-5 h-5 stroke-current fill-none stroke-1"
          >
            <line x1="6" x2="12" y1="9" y2="9"></line>
            <line x1="9" x2="9" y1="6" y2="12"></line>
            <circle cx="9" cy="9" r="6"></circle>
          </svg>
        </Button>
      </div>
    </div>
    <!-- editor content -->
    <div :style="zoomStyle" :class="cn('flex', loadingDocument && 'hidden')">
      <div id="lineNumber"></div>
      <div
        id="editor"
        class="grow"
        @contextmenu="showContextMenu"
        @dragenter.prevent
        @dragover.prevent
        @drop.prevent
      ></div>
    </div>
    <div
      class="fixed bg-surface md:bg-transparent hover:border hover:border-primary w-full md:w-auto bottom-0 md:bottom-4 py-2 md:py-0 right-0 md:right-4 grow flex items-end"
      style="z-index: 50"
    >
      <Transition name="fade">
        <Button
          v-if="range?.length"
          variant="outline-secondary"
          @click="showContextMenu"
          class="inline-flex md:hidden"
          >Menu</Button
        >
      </Transition>
      <span class="text-foreground/60 w-4 h-4 animate-spin" v-show="updating">
        <ArrowPathIcon class="w-4 h-4" />
      </span>
      <span
        v-if="contentHash?.hash"
        class="text-xs border-0 bg-surface p-1 font-mono me-3 ms-auto"
        :title="contentHash.hash"
        >Integrity: {{ contentHash.short }}</span
      >
      <span
        id="selection-hash"
        class="text-center text-xs border-0 bg-surface p-1 font-mono"
        >0:0</span
      >
    </div>
    <div v-if="loadingDocument" class="p-3">
      <ActivityIndicator>{{ 'Loading source' }}</ActivityIndicator>
    </div>
    <Transition name="fade">
      <CodingContextMenu @close="contextMenuClosed" />
    </Transition>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch, computed } from 'vue';
import Quill from 'quill';
import QuillCursors from 'quill-cursors';
import { formats } from '../../editor/EditorConfig.js';
import '../../editor/editor.css';
import { LineNumber } from '../../editor/LineNumber.js';
import { SelectionHighlightBG } from './editor/SelectionHighlightBG.js';
import { SelectionHash } from '../../editor/SelectionHash.js';
import CodingContextMenu from './contextMenu/CodingContextMenu.vue';
import { flashMessage } from '../../Components/notification/flashMessage.js';
import { useCodes } from '../../domain/codes/useCodes.js';
import { useRange } from './useRange.js';
import { useSelections } from './selections/useSelections.js';
import { useCodingEditor } from './useCodingEditor.js';
import { useContextMenu } from './contextMenu/useContextMenu.js';
import { ArrowPathIcon } from '@heroicons/vue/20/solid';
import Headline1 from '../../Components/layout/Headline1.vue';
import { asyncTimeout } from '../../utils/asyncTimeout.js';
import ActivityIndicator from '../../Components/ActivityIndicator.vue';
import { cn } from '../../utils/css/cn.js';
import { createHash } from '../../utils/createHash.js';
import { useZoom } from '../../editor/useZoom.js';
import Button from '../../Components/interactive/Button.vue';

const editorContent = ref('');
const contextMenu = useContextMenu();
const { selected, createSelection, markToDelete } = useSelections();
const { observe, selections, selectionsByIndex } = useCodes();
const { prevRange, setRange, range } = useRange();
const { setInstance, dispose } = useCodingEditor();
const { zoom, setZoom } = useZoom();

const zoomStyle = computed(() => {
  const z = zoom.value || 1.0;
  return {
    transform: `scale(${z})`,
    transformOrigin: 'top left',
    width: z === 1 ? '100%' : `calc(100% / ${z})`,
  };
});
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
const contentHash = ref('');

let quillInstance;
onMounted(() => {
  quillInstance = new Quill('#editor', {
    theme: 'snow',
    formats: formats.concat(['id', 'title', 'class']),
    placeholder: 'Start writing or paste content...',
    modules: {
      syntax: false,
      history: false,
      toolbar: false,
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
    added: (docs, allDocs) => {
      // XXX: this currently re-renders the entire editor source
      // which runs smooth for now but we may have to test this for larger documents!
      hl.add(allDocs ?? docs);
    },
    updated: (docs, allDocs) => {
      // XXX: this currently re-renders the entire editor source
      // which runs smooth for now but we may have to test this for larger documents!
      const target = allDocs ?? docs;
      const toRemove = target.filter((d) => d.code.active === false);
      const toKeep = target.filter((d) => d.code.active !== false);
      hl.removeAll(toRemove);
      hl.add(toKeep);
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
  updateHash().catch(console.error);
});

watch(
  () => props.source,
  async (newValue /*, oldValue*/) => {
    //quillInstance.setText(newValue)
    loadingDocument.value = true;
    await asyncTimeout(300);
    quillInstance.clipboard.dangerouslyPasteHTML(newValue.content);
    await updateHash();
  }
);

const updateHash = async () => {
  const data = quillInstance.getContents();
  const hash = (await createHash(data)) ?? '';
  const short = hash.substring(0, 8);
  contentHash.value = {
    hash,
    short,
  };
};

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
  if (event.shiftKey) {
    // Allow the browser's context menu to appear
    return;
  }
  const isMobile = event.pointerType === 'touch' || event.type === 'touchstart';

  event.preventDefault();
  event.stopPropagation();

  // XXX: it should be noted, that on Safari and mobile
  // browsers there is always a selection > 0 because they
  // try to auto-select some text.
  const selectedArea = quillInstance.getSelection();
  const hasSelection = selectedArea?.length;
  const linkedCodeId = event.target.getAttribute('data-code-id');
  const currentSelections = selectionsByIndex(
    selectedArea?.index,
    linkedCodeId
  );

  const hasLinked = !!currentSelections?.length;
  if (!hasSelection && !hasLinked) {
    return;
  }

  markToDelete(hasLinked ? currentSelections : null);

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

  const contextMenuElement = document.getElementById('contextMenu');

  const windowHeight = window.innerHeight;
  const windowWidth = window.innerWidth;

  let menuWidth = windowWidth / 4;
  if (menuWidth <= 400) menuWidth = windowWidth;

  let mouseX = event.clientX;
  if (mouseX < menuWidth / 2) mouseX = menuWidth / 2;

  let menuX = mouseX - menuWidth / 2;
  const offsetX = windowWidth - (mouseX + menuWidth / 2);
  if (offsetX < 0) menuX += offsetX;

  // contextMenuElement.style.left = `${menuX}px`;
  // contextMenuElement.style.width = `${menuWidth}px`;
  // contextMenuElement.style.maxHeight = `${windowHeight / 3}px`;
  // contextMenuElement.classList.remove('hidden');
  //
  // // Force a slight layout update so we can measure the contextMenu's dimensions
  contextMenuElement.offsetHeight;
  const offsetY =
    event.clientY + contextMenuElement.offsetHeight > windowHeight
      ? windowHeight - contextMenuElement.offsetHeight
      : event.clientY + 20;
  //
  // if (event.clientY + contextMenuElement.offsetHeight > windowHeight) {
  //   // If the context menu would go out of bounds, adjust its top position
  //   contextMenuElement.style.top = `${windowHeight - contextMenuElement.offsetHeight}px`;
  // } else {
  //   contextMenuElement.style.top = `${event.clientY + 20}px`;
  // }

  contextMenu.open(null, {
    left: menuX,
    top: isMobile ? 0 : offsetY,
    width: menuWidth,
    maxHeight: windowHeight < 720 ? windowHeight / 1.5 : windowHeight / 3,
  });
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
